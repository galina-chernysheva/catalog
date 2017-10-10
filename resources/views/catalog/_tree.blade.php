<?php
/**
 * @var $categories [ ['id' => '', 'parent_id' => '', 'title' => '', 'url' -> '', 'branch' => []] , ... ]
 */
?>

@foreach ($categories as $category)
    @if ($loop->first)
        <ul @if (empty($loop->parent_id)) class="top-tree-level" @endif>
    @endif
            <li><a href="{{ $category['url'] }}">{{ $category['title'] }}</a></li>
            @if (!empty($category['branch']))
                {!! view('catalog._tree', ['categories' => $category['branch']]) !!}
            @endif
    @if ($loop->last)
        </ul>
    @endif
@endforeach
