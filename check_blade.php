<?php
// Check blade if/endif structure
$file = file_get_contents('/app/resources/views/livewire/video-player.blade.php');
$lines = explode("\n", $file);

$depth = 0;
$stack = [];

foreach ($lines as $num => $line) {
    $lineNum = $num + 1;
    
    // Count @if (not inside comments or strings ideally)
    if (preg_match('/@if\s*\(/', $line)) {
        $depth++;
        $stack[] = ['type' => 'if', 'line' => $lineNum, 'code' => trim($line)];
        echo "Line $lineNum: @if (depth=$depth)\n";
    }
    
    if (preg_match('/@elseif\s*\(/', $line)) {
        echo "Line $lineNum: @elseif (depth=$depth)\n";
    }
    
    if (preg_match('/@else\b/', $line) && !preg_match('/@elseif/', $line)) {
        echo "Line $lineNum: @else (depth=$depth)\n";
    }
    
    if (preg_match('/@endif\b/', $line)) {
        if (empty($stack)) {
            echo "Line $lineNum: *** EXTRA @endif! ***\n";
        } else {
            $opened = array_pop($stack);
            echo "Line $lineNum: @endif (closes line {$opened['line']}, depth=" . ($depth-1) . ")\n";
        }
        $depth--;
    }
}

if (!empty($stack)) {
    echo "\n*** UNCLOSED @if statements: ***\n";
    foreach ($stack as $item) {
        echo "Line {$item['line']}: {$item['code']}\n";
    }
}

echo "\nFinal depth: $depth\n";
