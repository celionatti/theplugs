@extends('layouts.admin')

@section('title', $title ?? 'Admin Dashboard')

@section('content')
    <Adminheader />

    <Statscards />

    <Adminpackages />
@endsection