<!-- Хранится в resources/views/catalog/index.blade.php -->

@php
    use \DaveJamesMiller\Breadcrumbs\Facades\Breadcrumbs;
    /**
     * @var \App\Category $category
     */
@endphp

@extends('layouts.app')

@section('title', $category->title)

@section('breadcrumbs')
    {{ Breadcrumbs::render('category', $category) }}
@endsection

@section('header', $category->title)

@section('content')
    @php
        $url = $category->getFullUrl();
    @endphp
    <form class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2">ID</label>
            <div class="col-sm-10">{{ $category->id }}</div>
        </div>
        <div class="form-group">
            <label class="col-sm-2">URL</label>
            <div class="col-sm-10"><a href="{{ $url }}">{{ $url }}</a></div>
        </div>
        @if ($category->children()->count() > 0)
            <div class="form-group">
                <label class="col-sm-2">Подкатегории</label>
                <div class="col-sm-10">{!! view('catalog._tree', ['categories' => $category->children]) !!}</div>
            </div>
        @endif
    </form>
@endsection
