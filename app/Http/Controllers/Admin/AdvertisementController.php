<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    /**
     * Display a listing of advertisements.
     */
    public function index(Request $request)
    {
        $query = Advertisement::query();

        // Filter by position
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $ads = $query->orderBy('position')->orderBy('order')->paginate(20);

        return view('admin.advertisements.index', [
            'ads' => $ads,
            'positions' => Advertisement::POSITIONS,
            'types' => Advertisement::TYPES,
        ]);
    }

    /**
     * Show the form for creating a new advertisement.
     */
    public function create()
    {
        return view('admin.advertisements.create', [
            'positions' => Advertisement::POSITIONS,
            'types' => Advertisement::TYPES,
            'sizes' => Advertisement::SIZES,
        ]);
    }

    /**
     * Store a newly created advertisement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|in:' . implode(',', array_keys(Advertisement::POSITIONS)),
            'type' => 'required|in:' . implode(',', array_keys(Advertisement::TYPES)),
            'code' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'link' => 'nullable|url',
            'size' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'pages' => 'nullable|array',
            'show_on_mobile' => 'boolean',
            'show_on_desktop' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('ads', 'public');
        }

        // Set default values
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['show_on_mobile'] = $request->boolean('show_on_mobile', true);
        $validated['show_on_desktop'] = $request->boolean('show_on_desktop', true);
        $validated['order'] = $request->input('order', 0);

        unset($validated['image']);

        Advertisement::create($validated);

        return redirect()->route('admin.advertisements.index')
            ->with('success', 'Iklan berhasil dibuat!');
    }

    /**
     * Show the form for editing the specified advertisement.
     */
    public function edit(Advertisement $advertisement)
    {
        return view('admin.advertisements.edit', [
            'ad' => $advertisement,
            'positions' => Advertisement::POSITIONS,
            'types' => Advertisement::TYPES,
            'sizes' => Advertisement::SIZES,
        ]);
    }

    /**
     * Update the specified advertisement.
     */
    public function update(Request $request, Advertisement $advertisement)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|in:' . implode(',', array_keys(Advertisement::POSITIONS)),
            'type' => 'required|in:' . implode(',', array_keys(Advertisement::TYPES)),
            'code' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            'link' => 'nullable|url',
            'size' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'pages' => 'nullable|array',
            'show_on_mobile' => 'boolean',
            'show_on_desktop' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($advertisement->image_path) {
                Storage::disk('public')->delete($advertisement->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('ads', 'public');
        }

        // Set boolean values
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['show_on_mobile'] = $request->boolean('show_on_mobile', false);
        $validated['show_on_desktop'] = $request->boolean('show_on_desktop', false);

        unset($validated['image']);

        $advertisement->update($validated);

        return redirect()->route('admin.advertisements.index')
            ->with('success', 'Iklan berhasil diperbarui!');
    }

    /**
     * Remove the specified advertisement.
     */
    public function destroy(Advertisement $advertisement)
    {
        // Delete image if exists
        if ($advertisement->image_path) {
            Storage::disk('public')->delete($advertisement->image_path);
        }

        $advertisement->delete();

        return redirect()->route('admin.advertisements.index')
            ->with('success', 'Iklan berhasil dihapus!');
    }

    /**
     * Toggle advertisement status.
     */
    public function toggle(Advertisement $advertisement)
    {
        $advertisement->update(['is_active' => !$advertisement->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $advertisement->is_active,
            'message' => $advertisement->is_active ? 'Iklan diaktifkan' : 'Iklan dinonaktifkan',
        ]);
    }

    /**
     * Track ad click (AJAX endpoint).
     */
    public function trackClick(Advertisement $advertisement)
    {
        $advertisement->incrementClick();

        return response()->json(['success' => true]);
    }

    /**
     * Get statistics for all ads.
     */
    public function stats()
    {
        $stats = [
            'total' => Advertisement::count(),
            'active' => Advertisement::where('is_active', true)->count(),
            'total_impressions' => Advertisement::sum('impressions'),
            'total_clicks' => Advertisement::sum('clicks'),
            'by_position' => Advertisement::selectRaw('position, count(*) as count, sum(impressions) as impressions, sum(clicks) as clicks')
                ->groupBy('position')
                ->get(),
        ];

        return view('admin.advertisements.stats', compact('stats'));
    }
}
