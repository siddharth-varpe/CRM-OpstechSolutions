@extends('layouts.app')

@section('title', '500 - Server Error')

@section('content')
<div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-rose-500/10 text-rose-500 mb-6 border border-rose-500/20">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h1 class="text-4xl font-black text-slate-800 dark:text-slate-100 tracking-tight mb-2">500</h1>
    <h2 class="text-xl font-semibold text-slate-700 dark:text-slate-300 mb-3">Internal Server Error</h2>
    <p class="text-slate-500 dark:text-slate-400 max-w-md mb-8 text-sm">An unexpected condition occurred on the server. Please try again or contact the system administrator.</p>
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Return to Dashboard
    </a>
</div>
@endsection
