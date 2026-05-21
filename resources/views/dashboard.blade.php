<div class="header">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px;">
        <div>
            <h1>Smart Locker Monitor</h1>
            <p>ระบบมอนิเตอร์สถานะตู้ Smart Locker ของลูกค้าแต่ละเจ้า</p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="
                border: none;
                background: #dc2626;
                color: #ffffff;
                padding: 10px 16px;
                border-radius: 10px;
                cursor: pointer;
                font-weight: bold;
            ">
                Logout
            </button>
        </form>
    </div>
</div>