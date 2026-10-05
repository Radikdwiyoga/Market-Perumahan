{{--
    Penanda opt-in untuk app.js: hanya halaman yang memuat komponen ini yang
    menyambungkan EventSource notifikasi real-time (PRD Â§70).
--}}
<div hidden data-notifications-source data-user="{{ auth()->id() }}" data-url="{{ route('notifications.stream') }}"></div>



