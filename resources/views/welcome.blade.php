<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>QY Assistant | Premium AI Experience</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind & Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://unpkg.com/@supabase/supabase-js@2"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Real-time Implementation (Pusher-JS + Laravel Echo) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #050505;
            color: #e5e7eb;
        }
        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .chat-gradient {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        .glow {
            box-shadow: 0 0 20px rgba(96, 165, 250, 0.2);
        }
        ::-webkit-scrollbar {
            width: 5px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased overflow-hidden" x-data="chatApp()" x-init="init()">
    <div class="flex h-screen w-full">
        <!-- Sidebar (History) -->
        <aside class="w-72 glass border-r h-full flex flex-col transition-all duration-300 transform" 
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full absolute'">
            <div class="p-6 border-b flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/20">
                        QY
                    </div>
                    <span class="font-semibold text-lg tracking-tight">Assistant</span>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-4 flex-1 overflow-y-auto space-y-2">
                <button class="w-full py-2 px-4 rounded-xl border border-dashed border-gray-700 text-gray-400 hover:border-blue-500 hover:text-blue-400 transition-all text-sm flex items-center justify-center gap-2 mb-6 group"
                        @click="newChat()">
                    <i data-lucide="plus" class="w-4 h-4 transition-transform group-hover:rotate-90"></i>
                    New Conversation
                </button>

                <template x-for="chat in chatHistory" :key="chat.id">
                    <div class="group relative px-4 py-3 rounded-xl hover:bg-white/5 cursor-pointer transition-all border border-transparent hover:border-white/10"
                         :class="currentChatId === chat.id ? 'bg-white/10 border-white/10' : ''"
                         @click="loadChat(chat)">
                        <div class="flex items-center gap-3">
                            <i data-lucide="message-square" class="w-4 h-4 text-gray-500"></i>
                            <span class="text-sm truncate text-gray-300" x-text="chat.title"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-4 border-t bg-black/20">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-500 to-pink-500 p-0.5">
                        <div class="w-full h-full rounded-full bg-[#050505] p-1 flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">Guest User</p>
                        <p class="text-xs text-gray-500 truncate">Pro Level</p>
                    </div>
                    <i data-lucide="settings" class="w-4 h-4 text-gray-400 cursor-pointer hover:text-white"></i>
                </div>
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main class="flex-1 flex flex-col relative bg-[#050505] h-full overflow-hidden">
            <!-- Header -->
            <header class="h-16 border-b glass px-6 flex items-center justify-between z-10">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div class="flex flex-col">
                        <h2 class="font-semibold text-sm">Gemini 1.5 Flash</h2>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Latency: 284ms</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex -space-x-2">
                        <img src="https://ui-avatars.com/api/?name=AI&background=0D8ABC&color=fff" class="w-6 h-6 rounded-full border-2 border-[#050505]" alt="AI">
                        <img src="https://ui-avatars.com/api/?name=Q&background=555&color=fff" class="w-6 h-6 rounded-full border-2 border-[#050505]" alt="System">
                    </div>
                    <button class="px-3 py-1 rounded-full bg-blue-600/10 text-blue-400 text-xs font-semibold hover:bg-blue-600/20 transition-all border border-blue-600/20">
                        Upgrade
                    </button>
                </div>
            </header>

            <!-- Messages Area -->
            <div id="messages-container" class="flex-1 overflow-y-auto px-4 py-10 space-y-8 scroll-smooth">
                <div x-show="messages.length === 0" class="h-full flex flex-col items-center justify-center text-center max-w-xl mx-auto space-y-6" x-cloak>
                    <div class="w-20 h-20 rounded-3xl bg-blue-600/10 flex items-center justify-center glow mb-4">
                        <i data-lucide="sparkles" class="w-10 h-10 text-blue-500"></i>
                    </div>
                    <h1 class="text-4xl font-bold tracking-tight">How can I help you today?</h1>
                    <p class="text-gray-400 text-lg">Experience the next generation of AI intelligence powered by QY Smart Assistant.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full pt-8">
                        <template x-for="prompt in suggestedPrompts" :key="prompt">
                            <button @click="userInput = prompt; sendMessage()" 
                                    class="p-4 rounded-2xl glass hover:bg-white/5 text-left text-sm text-gray-300 transition-all border border-white/5 hover:border-white/10 group">
                                <span x-text="prompt"></span>
                                <i data-lucide="arrow-right" class="w-3 h-3 float-right mt-1 opacity-0 group-hover:opacity-100 transition-all"></i>
                            </button>
                        </template>
                    </div>
                </div>

                <template x-for="(msg, index) in messages" :key="index">
                    <div class="max-w-4xl mx-auto flex gap-6 group" :class="msg.role === 'user' ? 'justify-end' : ''">
                        <div x-show="msg.role === 'assistant'" class="shrink-0 w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                            <i data-lucide="bot" class="w-6 h-6 text-white"></i>
                        </div>
                        
                        <div class="flex flex-col gap-2 max-w-[85%]" :class="msg.role === 'user' ? 'items-end' : ''">
                            <div class="px-6 py-4 rounded-3xl text-base leading-relaxed break-words"
                                 :class="msg.role === 'assistant' ? 'glass text-gray-200' : 'bg-blue-600 text-white shadow-xl shadow-blue-500/10'">
                                <template x-if="msg.role === 'assistant'">
                                    <div class="prose prose-invert max-w-none" x-html="renderMarkdown(msg.content)"></div>
                                </template>
                                <template x-if="msg.role === 'user'">
                                    <div x-text="msg.content"></div>
                                </template>
                            </div>
                            <span class="text-[10px] text-gray-500 uppercase font-bold tracking-widest px-2" x-text="msg.time"></span>
                        </div>

                        <div x-show="msg.role === 'user'" class="shrink-0 w-10 h-10 rounded-2xl bg-gradient-to-tr from-purple-500 to-pink-500 flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                </template>

                <!-- Typing Indicator -->
                <div x-show="isTyping" class="max-w-4xl mx-auto flex gap-6" x-cloak>
                    <div class="shrink-0 w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center">
                        <i data-lucide="bot" class="w-6 h-6 text-white"></i>
                    </div>
                    <div class="px-8 py-5 rounded-3xl glass flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-6 pb-10 bg-gradient-to-t from-[#050505] via-[#050505] to-transparent">
                <div class="max-w-4xl mx-auto relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-[28px] opacity-20 blur group-focus-within:opacity-40 transition-all"></div>
                    <div class="relative flex items-end gap-3 glass p-2 rounded-[24px] overflow-hidden focus-within:ring-2 focus-within:ring-blue-500/50 transition-all">
                        <button class="p-3 text-gray-400 hover:text-white transition-colors">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                        </button>
                        <textarea 
                            x-model="userInput" 
                            @keydown.enter.prevent="sendMessage()"
                            placeholder="Type your message here..."
                            class="flex-1 bg-transparent border-none focus:ring-0 text-white placeholder-gray-500 resize-none py-3 px-2 max-h-48 min-h-[44px]"
                            rows="1"
                        ></textarea>
                        <button 
                            @click="sendMessage()"
                            :disabled="!userInput.trim() || isTyping"
                            class="p-3 rounded-2xl bg-blue-600 text-white disabled:opacity-50 disabled:cursor-not-allowed hover:bg-blue-500 hover:scale-105 active:scale-95 transition-all shadow-lg shadow-blue-500/20">
                            <i data-lucide="send" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <p class="text-center text-[11px] text-gray-600 mt-4 tracking-wide">
                    QY Assistant can make mistakes. Check important info.
                </p>
            </div>
        </main>
    </div>

    <script>
        function chatApp() {
            return {
                sidebarOpen: true,
                isTyping: false,
                userInput: '',
                messages: [],
                chatHistory: [],
                currentChatId: null,
                systemToast: { show: false, message: '' },
                suggestedPrompts: [
                    "Help me write a Python script for a web scraper",
                    "What are the best practices for Laravel 12?",
                    "Tell me a story about a futuristic city",
                    "How do I optimize Gemini API responses?"
                ],

                init() {
                    lucide.createIcons();
                    this.loadHistory();
                    
                    // Initialize Laravel Echo for Real-time
                    window.Pusher = Pusher;
                    window.Echo = new Echo({
                        broadcaster: 'reverb',
                        key: '{{ env('REVERB_APP_KEY', 'qyassistantkey') }}',
                        wsHost: '{{ env('REVERB_HOST', 'localhost') }}',
                        wsPort: {{ env('REVERB_PORT', 8080) }},
                        wssPort: {{ env('REVERB_PORT', 8080) }},
                        forceTLS: ( '{{ env('REVERB_SCHEME', 'http') }}' === 'https' ),
                        enabledTransports: ['ws', 'wss'],
                    });

                    window.Echo.channel('system-notifications')
                        .listen('.system.message', (data) => {
                            console.log('Real-time Message Received:', data);
                            this.systemToast.message = data.message;
                            this.systemToast.show = true;
                            setTimeout(() => this.systemToast.show = false, 5000);
                        });

                    console.log('Echo Initialized for Reverb & System Notifications');

                    // Supabase Init (if you want to use it)
                    this.supabase = supabase.createClient(
                        '{{ config('services.supabase.url') }}' || 'https://ptbgfnsdheytlmeqcgyl.supabase.co',
                        '{{ config('services.supabase.key') }}' || 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
                    );

                    // Responsive sidebar
                    if (window.innerWidth < 1024) this.sidebarOpen = false;
                },

                loadHistory() {
                    const saved = localStorage.getItem('qy_chat_history');
                    if (saved) this.chatHistory = JSON.parse(saved);
                },

                saveHistory() {
                    localStorage.setItem('qy_chat_history', JSON.stringify(this.chatHistory));
                },

                async sendMessage() {
                    if (!this.userInput.trim() || this.isTyping) return;

                    const message = this.userInput;
                    this.userInput = '';
                    
                    const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    // Add user message
                    this.messages.push({
                        role: 'user',
                        content: message,
                        time: timestamp
                    });

                    this.scrollToBottom();
                    this.isTyping = true;

                    // Add empty assistant message for streaming
                    const assistantMsgIndex = this.messages.length;
                    this.messages.push({
                        role: 'assistant',
                        content: '',
                        time: timestamp
                    });

                    try {
                        // Use fetch for streaming
                        const url = `/api/chat/stream?message=${encodeURIComponent(message)}`;
                        const response = await fetch(url);
                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();

                        this.isTyping = false; // Hide typing indicator once stream starts

                        while (true) {
                            const { value, done } = await reader.read();
                            if (done) break;

                            const chunk = decoder.decode(value);
                            const lines = chunk.split('\n');

                            for (const line of lines) {
                                if (line.startsWith('data: ')) {
                                    const dataStr = line.replace('data: ', '').trim();
                                    
                                    if (dataStr === '[DONE]') break;
                                    
                                    try {
                                        const json = JSON.parse(dataStr);
                                        if (json.text) {
                                            this.messages[assistantMsgIndex].content += json.text;
                                            this.scrollToBottom();
                                        }
                                    } catch (e) {
                                        // Ignore parse errors for incomplete chunks
                                    }
                                }
                            }
                        }

                        // Create history entry
                        if (this.messages.length === 2) {
                            const newId = Date.now();
                            this.currentChatId = newId;
                            this.chatHistory.unshift({
                                id: newId,
                                title: message.substring(0, 30) + (message.length > 30 ? '...' : ''),
                                messages: JSON.parse(JSON.stringify(this.messages))
                            });
                        } else if (this.currentChatId) {
                            const chat = this.chatHistory.find(c => c.id === this.currentChatId);
                            if (chat) chat.messages = JSON.parse(JSON.stringify(this.messages));
                        }
                        this.saveHistory();

                    } catch (error) {
                        console.error('Chat Error:', error);
                        this.messages[assistantMsgIndex].content = "I'm sorry, I encountered an error. Please try again later.";
                    } finally {
                        this.isTyping = false;
                        this.scrollToBottom();
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                newChat() {
                    this.messages = [];
                    this.currentChatId = null;
                    if (window.innerWidth < 1024) this.sidebarOpen = false;
                },

                loadChat(chat) {
                    this.currentChatId = chat.id;
                    this.messages = JSON.parse(JSON.stringify(chat.messages));
                    if (window.innerWidth < 1024) this.sidebarOpen = false;
                    this.scrollToBottom();
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = document.getElementById('messages-container');
                        container.scrollTop = container.scrollHeight;
                    });
                },

                renderMarkdown(text) {
                    // Very simple markdown-to-html (you might want a library like DOMPurify + marked)
                    return text
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.*?)\*/g, '<em>$1</em>')
                        .replace(/```([\s\S]*?)```/g, '<pre class="bg-black/40 p-4 rounded-xl my-4 overflow-x-auto"><code>$1</code></pre>')
                        .replace(/`(.*?)`/g, '<code class="bg-white/10 px-1.5 py-0.5 rounded text-sm font-mono">$1</code>')
                        .replace(/\n/g, '<br>');
                }
            }
        }
    </script>
</body>
</html>
