<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>پیام‌های واتساپ (Realtime)</title>
    <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
    <style>
        body{font-family:sans-serif;background:#fafafa;margin:0;padding:24px;direction:rtl;}
        .box{background:#fff;border:1px solid #ddd;border-radius:12px;padding:16px;height:75vh;overflow:auto;}
        pre{background:#111;color:#eee;padding:10px;border-radius:6px;white-space:pre-wrap;}
    </style>
</head>
<body>
<h2>پیام‌های واتساپ در لحظه</h2>
<div class="box" id="messages"><p>در انتظار پیام...</p></div>

<script>
    // ⚙️ تنظیمات Pusher
    const pusher = new Pusher('PUSHER_APP_KEY', {
        cluster: 'mt1',
        forceTLS: false,
        wsHost: '127.0.0.1', // آدرس سرور Pusher یا Soketi
        wsPort: 6001,
        enabledTransports: ['ws', 'wss']
    });

    const channel = pusher.subscribe('whatsapp');
    const box = document.getElementById('messages');

    channel.bind('message.received', (data) => {
        if (box.innerText.includes('در انتظار')) box.innerHTML = '';
        const msg = document.createElement('div');
        msg.innerHTML = `<pre>${JSON.stringify(data.payload, null, 2)}</pre>`;
        box.prepend(msg);
    });
</script>
</body>
</html>
