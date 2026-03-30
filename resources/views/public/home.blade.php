@extends('layouts.public')

@section('title', 'Inicio')

@section('content')

    @include('public.sections.hero')
    @include('public.sections.servicios')
    @include('public.sections.nosotros')
    @include('public.sections.contacto')

@endsection