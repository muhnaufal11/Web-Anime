@props(['position', 'page' => 'all', 'class' => ''])

@php
    use App\Models\Advertisement;
    $ads = Advertisement::getByPosition($position, $page);
@endphp

@if($ads->count() > 0)
<div class="ad-container ad-{{ $position }} {{ $class }}" data-position="{{ $position }}">
    @foreach($ads as $ad)
    <div class="ad-unit" 
         data-ad-id="{{ $ad->id }}"
         @if(!$ad->show_on_mobile) data-hide-mobile @endif
         @if(!$ad->show_on_desktop) data-hide-desktop @endif>
        {!! $ad->render() !!}
    </div>
    @endforeach
</div>
@endif
