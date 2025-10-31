// chat-widget.js — AgroConnect Smart Chatbot with open/close button + multilingual voice

(function () {
  if (document.getElementById("agro-chat-toggle")) return;

  document.addEventListener("DOMContentLoaded", () => {
    // 🤖 Floating chat toggle button
    const toggleBtn = document.createElement("div");
    toggleBtn.id = "agro-chat-toggle";
    toggleBtn.innerHTML = "🤖";
    toggleBtn.style = `
      position: fixed;
      right: 25px;
      bottom: 25px;
      background: #2b7a0b;
      color: #fff;
      font-size: 26px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(0,0,0,0.3);
      z-index: 9999;
      transition: transform 0.3s ease, background 0.3s ease;
    `;
    toggleBtn.addEventListener("mouseenter", () => {
      toggleBtn.style.background = "#3da60f";
      toggleBtn.style.transform = "scale(1.1)";
    });
    toggleBtn.addEventListener("mouseleave", () => {
      toggleBtn.style.background = "#2b7a0b";
      toggleBtn.style.transform = "scale(1)";
    });
    document.body.appendChild(toggleBtn);

    // 💬 Chat widget container (hidden by default)
    const widget = document.createElement("div");
    widget.id = "agro-chat-widget";
    widget.style = `
      position: fixed;
      right: 25px;
      bottom: 100px;
      width: 340px;
      max-width: 90%;
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      display: none;
      flex-direction: column;
      font-family: Arial, sans-serif;
      z-index: 10000;
      overflow: hidden;
      animation: fadeInUp 0.3s ease;
    `;

    widget.innerHTML = `
      <div style="background:#2b7a0b;color:#fff;padding:10px;border-radius:12px 12px 0 0;
        font-weight:bold;display:flex;justify-content:space-between;align-items:center">
        🌾 AgroConnect Chat
        <div id="chat-close" style="cursor:pointer;font-size:18px;">❌</div>
      </div>
      <div style="padding:8px;background:#fff;display:flex;justify-content:space-between;align-items:center">
        <label style="font-size:13px;">Language:</label>
        <select id="lang-select" style="border:1px solid #ccc;border-radius:6px;padding:4px 6px;font-size:13px;">
          <option value="auto" selected>🌐 Auto Detect</option>
          <option value="en">English</option>
          <option value="hi">हिन्दी</option>
          <option value="kn">ಕನ್ನಡ</option>
        </select>
      </div>
      <div id="chat-body" style="background:#f7f7f7;height:260px;overflow-y:auto;padding:10px;font-size:14px;border-top:1px solid #ddd;border-bottom:1px solid #ddd;"></div>
      <div style="display:flex;gap:6px;padding:8px;background:#fff;">
        <button id="btn-speak" style="padding:8px 10px;background:#2b7a0b;color:#fff;border:none;border-radius:6px;cursor:pointer;">🎤</button>
        <input id="chat-input" placeholder="Type message..." style="flex:1;padding:8px;border:1px solid #ccc;border-radius:6px;" />
        <button id="btn-send" style="padding:8px 10px;background:#2b7a0b;color:#fff;border:none;border-radius:6px;cursor:pointer;">➤</button>
      </div>
    `;
    document.body.appendChild(widget);

    const chatBody = document.getElementById("chat-body");
    const chatInput = document.getElementById("chat-input");
    const btnSend = document.getElementById("btn-send");
    const btnSpeak = document.getElementById("btn-speak");
    const langSelect = document.getElementById("lang-select");
    const closeBtn = document.getElementById("chat-close");

    // Toggle open/close behavior
    toggleBtn.addEventListener("click", () => {
      widget.style.display = "flex";
      toggleBtn.style.display = "none";
    });
    closeBtn.addEventListener("click", () => {
      widget.style.display = "none";
      toggleBtn.style.display = "flex";
    });

    const appendMsg = (who, msg) => {
      const div = document.createElement("div");
      div.style.margin = "6px 0";
      div.style.whiteSpace = "pre-wrap";
      div.innerHTML = `<b>${who}:</b> ${msg}`;
      chatBody.appendChild(div);
      chatBody.scrollTop = chatBody.scrollHeight;
    };

    const updateLastBot = (msg) => {
      const items = chatBody.querySelectorAll("div");
      for (let i = items.length - 1; i >= 0; i--) {
        if (items[i].innerHTML.includes("⏳")) {
          items[i].innerHTML = `<b>🤖 Bot:</b> ${msg}`;
          chatBody.scrollTop = chatBody.scrollHeight;
          return;
        }
      }
      appendMsg("🤖 Bot", msg);
    };

    // Language detection
    const detectLang = (text) => {
      if (/\p{Script=Kannada}/u.test(text)) return "kn";
      if (/\p{Script=Devanagari}/u.test(text)) return "hi";
      return "en";
    };

    // Text-to-Speech
    const speakText = (text, lang) => {
      if (!("speechSynthesis" in window)) return;
      speechSynthesis.cancel();
      const utter = new SpeechSynthesisUtterance(text);
      utter.lang = lang === "hi" ? "hi-IN" : lang === "kn" ? "kn-IN" : "en-US";
      speechSynthesis.speak(utter);
    };

    const sendMessage = async (msg) => {
      if (!msg.trim()) return;
      appendMsg("🧑 You", msg);
      chatInput.value = "";
      appendMsg("🤖 Bot", "⏳ Thinking...");

      let selectedLang = langSelect.value;
      if (selectedLang === "auto") selectedLang = detectLang(msg);

      try {
        const prompt = `Reply only in ${selectedLang === "hi" ? "Hindi" : selectedLang === "kn" ? "Kannada" : "English"} language. User said: ${msg}`;
        const resp = await fetch("/Agroconnect/chatbot.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ message: msg, lang: selectedLang }),
        });

        const data = await resp.json();
        if (data.reply) {
          updateLastBot(data.reply);
          speakText(data.reply, selectedLang);
        } else updateLastBot("⚠️ No reply from chatbot");
      } catch (err) {
        console.error(err);
        updateLastBot("⚠️ Error contacting chatbot.");
      }
    };

    btnSend.addEventListener("click", () => sendMessage(chatInput.value));
    chatInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        sendMessage(chatInput.value);
      }
    });

    // 🎤 Voice input
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
      const recog = new SpeechRecognition();
      recog.interimResults = false;
      recog.maxAlternatives = 1;

      btnSpeak.addEventListener("click", () => {
        recog.lang =
          langSelect.value === "hi"
            ? "hi-IN"
            : langSelect.value === "kn"
            ? "kn-IN"
            : "en-IN";
        recog.start();
      });

      recog.onresult = (e) => {
        const transcript = e.results[0][0].transcript;
        sendMessage(transcript);
      };

      recog.onerror = (e) => appendMsg("System", "🎤 Voice error: " + e.error);
    } else {
      btnSpeak.disabled = true;
      btnSpeak.title = "Speech recognition not supported";
    }
  });
})();
