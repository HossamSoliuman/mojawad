@extends('layouts.app')
@section('title', __('Privacy policy'))
@section('content')
<div class="page-hd">
  <div class="wrap">
    <h1><i class="fas fa-shield-halved gold"></i> {{ __('Privacy policy') }}</h1>
    <p>{{ __('How Mojawad handles your data.') }}</p>
  </div>
</div>

<div class="wrap legal">
  <p class="legal-date">{{ __('Last updated :date', ['date' => \Illuminate\Support\Carbon::parse('2026-08-13')->translatedFormat('j F Y')]) }}</p>

  <h2>{{ __('What we collect') }}</h2>
  <ul>
    <li>{{ __('Account details — your name and email address, and only when you create an account or sign in.') }}</li>
    <li>{{ __('Your library — the recitations you like, save, or follow, so they stay with you across devices.') }}</li>
    <li>{{ __('Listening activity — what you played and for how long, which powers the listening insights on your own profile.') }}</li>
    <li>{{ __('Technical data — ordinary server logs and a session cookie that keeps you signed in.') }}</li>
  </ul>

  <h2>{{ __('How we use it') }}</h2>
  <ul>
    <li>{{ __('To keep your library, follows, and preferences in sync wherever you sign in.') }}</li>
    <li>{{ __('To show you your own listening statistics on your profile page.') }}</li>
    <li>{{ __('To understand which recitations are most useful, so we can improve the collection.') }}</li>
  </ul>

  <h2>{{ __('What we never do') }}</h2>
  <ul>
    <li>{{ __('We do not sell, rent, or trade your personal data to anyone.') }}</li>
    <li>{{ __('We do not build advertising profiles or run behavioural ad tracking.') }}</li>
    <li>{{ __('We do not share your account or listening data with third parties.') }}</li>
  </ul>

  <h2>{{ __('Third-party services') }}</h2>
  <p>{{ __('Recitation audio is hosted and delivered through the Internet Archive (archive.org).') }}</p>
  <p>{{ __('We publish recitations to our own Facebook page and YouTube channel. That sends our published content outward only — no information about you or your account is included.') }}</p>

  <h2>{{ __('Cookies') }}</h2>
  <p>{{ __('We use a session cookie to keep you signed in. Before you sign in, your likes and saves are kept in your browser and never leave your device.') }}</p>

  {{-- Anchor target for the App Dashboard's "Data deletion instructions URL". --}}
  <h2 id="data-deletion">{{ __('Keeping and deleting your data') }}</h2>
  <p>{{ __('We keep your data for as long as your account exists. Write to us and we will delete your account and everything attached to it.') }}</p>

  <h2>{{ __('Children') }}</h2>
  <p>{{ __('Mojawad publishes Quran recitation suitable for every age. We do not knowingly collect data from children under 13.') }}</p>

  <h2>{{ __('Changes to this policy') }}</h2>
  <p>{{ __('If this policy changes, the new version appears on this page with an updated date above.') }}</p>

  <h2>{{ __('Contact') }}</h2>
  <p>{!! __('Questions about your data? Email us at :email.', ['email' => '<a href="mailto:mojawad.org@gmail.com" dir="ltr">mojawad.org@gmail.com</a>']) !!}</p>
</div>
@endsection
