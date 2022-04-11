<?php

namespace App\Console\Commands;

use Illuminate\Console\Command as IlluminateCommand;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Blade;

abstract class Command extends IlluminateCommand
{
    protected function makeTable(array|Arrayable $headings, array|Arrayable $rows, string $headingStyle = 'pr-1', string $cellStyle = ''): string
    {
        return Blade::render(<<<'HTML'
            <table>
                <thead>
                    <tr>
                        @foreach($headings as $heading)
                            <th class="{{ $headingStyle }}">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="{{ $row['_rowStyle'] ?? '' }}">
                            @foreach($row as $key => $cell)
                                @if(is_string($key) && Str::of($key)->startsWith('_')) @continue @endif
                                @php
                                    $itemStyle = null;

                                    if (is_array($cell)) {
                                        [$itemStyle, $cell] = $cell;
                                    }
                                @endphp


                                @if($loop->index === 0)
                                    <th class="{{ $cellStyle }} {{ $row['_titleStyle'] ?? $itemStyle ?? '' }}">{{ $cell }}</th>
                                @else
                                    <td class="{{ $cellStyle }} {{ $row['_cellStyle'] ?? $itemStyle ?? '' }}">{{ $cell }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        HTML, compact('headings', 'rows', 'headingStyle', 'cellStyle'));
    }
}
