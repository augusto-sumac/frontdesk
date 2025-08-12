@extends('layouts.app')

@section('title', 'FrontDesk - Conversa')

@section('styles')
    <style>
        .chat-container {
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .message {
            margin-bottom: 15px;
            display: flex;
        }
        .message.sent {
            justify-content: flex-end;
        }
        .message.received {
            justify-content: flex-start;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        .message.sent .message-bubble {
            background-color: #007bff;
            color: white;
        }
        .message.received .message-bubble {
            background-color: white;
            border: 1px solid #dee2e6;
        }
        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        .chat-input {
            border-top: 1px solid #dee2e6;
            padding: 15px;
            background-color: white;
        }
        .typing-indicator {
            font-style: italic;
            color: #6c757d;
            padding: 10px;
        }
    </style>
@endsection

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('messages.index') }}" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
        <h1 class="h2 mb-0">{{ $thread['subject'] ?? 'Conversa' }}</h1>
                        </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-archive"></i> Arquivar
                        </button>
        </div>
                    </div>
                </div>

                @if(isset($error))
                    <div class="alert alert-danger" role="alert">
                        {{ $error }}
                    </div>
                @endif

<div class="row">
    <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="chat-container">
                            <div class="chat-messages" id="chatMessages">
                                @if(count($messages ?? []) > 0)
                                    @foreach($messages as $message)
                                <div class="message {{ ($message['isFromGuest'] ?? false) ? 'received' : 'sent' }}">
                                            <div class="message-bubble">
                                                <div class="message-content">
                                            {{ $message['body'] ?? 'Sem conteúdo' }}
                                                </div>
                                                <div class="message-time">
                                                    {{ \Carbon\Carbon::parse($message['createdAt'] ?? now())->format('d/m/Y H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-comment fa-2x mb-2"></i>
                                <p>Nenhuma mensagem nesta conversa</p>
                                    </div>
                                @endif
                            </div>

                            <div class="chat-input">
                        <form id="messageForm" onsubmit="sendMessage(event)">
                                        <div class="input-group">
                                <input type="text" class="form-control" id="messageInput" placeholder="Digite sua mensagem..." required>
                                <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
    // Auto-scroll to bottom of chat
    function scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

    // Send message function
    function sendMessage(event) {
        event.preventDefault();
            
        const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
        
            if (!message) return;

        // Add message to chat immediately (optimistic UI)
        const chatMessages = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message sent';
        messageDiv.innerHTML = `
            <div class="message-bubble">
                <div class="message-content">${message}</div>
                <div class="message-time">${new Date().toLocaleString('pt-BR')}</div>
            </div>
        `;
        chatMessages.appendChild(messageDiv);
        
        // Clear input
            messageInput.value = '';
        
        // Scroll to bottom
        scrollToBottom();
        
        // Send to server
            fetch('{{ route("messages.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
            body: JSON.stringify({
                threadId: '{{ $thread["threadId"] ?? "" }}',
                message: message
            })
            })
            .then(response => response.json())
            .then(data => {
            if (!data.success) {
                    alert('Erro ao enviar mensagem: ' + data.error);
                }
            })
            .catch(error => {
            alert('Erro ao enviar mensagem: ' + error);
            });
        }

    // Auto-scroll on page load
    document.addEventListener('DOMContentLoaded', function() {
        scrollToBottom();
    });

    // Focus on input when page loads
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('messageInput').focus();
    });
    </script>
@endsection 