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
        $branch = $category->getBranchTree();
    @endphp
    <form class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-2">Код</label>
            <div class="col-sm-10">{{ $category->getCode() }}</div>
        </div>
        <div class="form-group">
            <label class="col-sm-2">URL</label>
            <div class="col-sm-10"><a href="{{ $url }}">{{ $url }}</a></div>
        </div>
        @if (!empty($branch))
            <div class="form-group">
                <label class="col-sm-2">Подкатегории</label>
                <div class="col-sm-10">{!! view('catalog._tree', ['categories' => $branch]) !!}</div>
            </div>
        @endif
    </form>
@endsection
