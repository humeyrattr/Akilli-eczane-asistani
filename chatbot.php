<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$pageTitle = 'Sağlık Asistanı';
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
  <div>
    <h1><i class="fas fa-robot" style="color:var(--primary)"></i> Sağlık Asistanı</h1>
    <p>İlaçlar ve genel sağlık konularında sorularınızı yanıtlayabilirim</p>
  </div>
</div>

<div class="chat-container" id="chatContainer">
  <div class="chat-messages" id="chatMessages">
    <!-- Karşılama mesajı -->
    <div class="chat-msg bot">
      <div class="chat-avatar bot"><i class="fas fa-robot"></i></div>
      <div class="chat-bubble">
        Merhaba! 👋 Ben <strong>Panacea Care Sağlık Asistanı</strong>yım.<br><br>
        İlaçlar, dozlar veya genel sağlık konularında sorularınızı yanıtlamaya hazırım.<br><br>
        <em>Örnek:</em> "Aspirin nedir?", "İlaç dozumu atlarsam ne yapmalıyım?", "Su içmek neden önemli?"
      </div>
    </div>
    <!-- Hızlı sorular -->
    <div style="display:flex;flex-wrap:wrap;gap:8px;padding:0 0 0 44px;margin-top:-4px;">
      <button class="quick-btn" onclick="sendMessage('Aspirin hakkında bilgi ver')">💊 Aspirin nedir?</button>
      <button class="quick-btn" onclick="sendMessage('İlaç dozumu atlarsam ne yapmalıyım?')">⏰ Doz atladım ne yapayım?</button>
      <button class="quick-btn" onclick="sendMessage('İlaç etkileşimi nedir?')">⚠️ İlaç etkileşimi</button>
      <button class="quick-btn" onclick="sendMessage('Ne sorabilirm?')">❓ Neler sorabilirim?</button>
    </div>
  </div>
  <div class="chat-input-area">
    <input type="text" id="chatInput" class="chat-input" placeholder="Sorunuzu yazın... (Enter ile gönderin)" autocomplete="off">
    <button id="chatSendBtn" class="chat-send" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
  </div>
</div>

<style>
.quick-btn{background:var(--bg3);border:1px solid var(--border);color:var(--text2);padding:6px 12px;border-radius:20px;font-size:.78rem;cursor:pointer;transition:.2s;font-family:inherit;}
.quick-btn:hover{border-color:var(--primary);color:var(--primary);}
</style>

<?php $extraScripts = <<<JS
<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput    = document.getElementById('chatInput');

chatInput.addEventListener('keydown', e => { if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); } });

async function sendMessage(text) {
    const msg = text || chatInput.value.trim();
    if(!msg) return;
    chatInput.value = '';

    appendMessage('user', msg);
    showTyping();

    try {
        const res  = await fetch(SITE_URL+'/api/chatbot.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:msg,csrf:CSRF_TOKEN})});
        const data = await res.json();
        removeTyping();
        appendMessage('bot', data.answer || 'Üzgünüm, bu konuda bilgim yok. Lütfen doktorunuza danışın.');
    } catch(e) {
        removeTyping();
        appendMessage('bot', 'Bir hata oluştu, lütfen tekrar deneyin.');
    }
}

function appendMessage(role, text) {
    const isBot = role === 'bot';
    const div = document.createElement('div');
    div.className = `chat-msg \${role}`;
    div.innerHTML = `
        <div class="chat-avatar \${isBot?'bot':'user-av'}">\${isBot?'<i class="fas fa-robot"></i>':'<i class="fas fa-user"></i>'}</div>
        <div class="chat-bubble">\${text.replace(/\\n/g,'<br>')}</div>`;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showTyping() {
    const div = document.createElement('div');
    div.className = 'chat-msg bot'; div.id = 'typingIndicator';
    div.innerHTML = '<div class="chat-avatar bot"><i class="fas fa-robot"></i></div><div class="chat-bubble"><div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>';
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
function removeTyping() {
    const el = document.getElementById('typingIndicator');
    if(el) el.remove();
}
</script>
JS;
include __DIR__ . '/includes/footer.php'; ?>
