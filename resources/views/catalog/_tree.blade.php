<?php
/**
 * @var $categories \App\Category[]
 */
?>

@foreach ($categories as $category)
    @if ($loop->first)
        <ul @if (empty($loop->parent)) class="top-tree-level" @endif>
    @endif
            <li><a href="{{ $category->getFullUrl() }}">{{ $category->title }}</a></li>
            @if ($category->children()->count() > 0 )
                {!! view('catalog._tree', ['categories' => $category->children]) !!}
            @endif
    @if ($loop->last)
        </ul>
    @endif
@endforeach
