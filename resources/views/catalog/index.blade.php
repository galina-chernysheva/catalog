<!-- Хранится в resources/views/catalog/index.blade.php -->

<?php
    use \DaveJamesMiller\Breadcrumbs\Facades\Breadcrumbs;
?>

@extends('layouts.app')

@section('breadcrumbs')
    {{ Breadcrumbs::render('catalog') }}
@endsection

@section('header', $name)

@section('content')
    @if (empty($categories))
        Каталог пуст
    @else
        {!! view('catalog._tree', ['categories' => $categories]) !!}
    @endif
@endsection
