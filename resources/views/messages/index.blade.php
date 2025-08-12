@extends('layouts.app')

@section('title', 'FrontDesk - Mensagens')

@section('content')
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mensagens</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Atualizar</button>
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
                        @if(count($threads ?? []) > 0)
                            <div class="list-group">
                                @foreach($threads as $thread)
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        {{ substr($thread['lastMessage'] ?? 'M', 0, 1) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{ $thread['subject'] ?? 'Conversa sem título' }}</h6>
                                                    <p class="mb-1 text-muted">{{ Str::limit($thread['lastMessage'] ?? 'Sem mensagens', 100) }}</p>
                                                    <small class="text-muted">
                                                        {{ $thread['channelId'] ?? 'N/A' }} • 
                                                        {{ \Carbon\Carbon::parse($thread['updatedAt'] ?? now())->format('d/m/Y H:i') }} • 
                                                        <span class="text-capitalize">{{ $thread['status'] ?? 'unknown' }}</span>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                @if($thread['isUnread'] ?? false)
                                                    <span class="badge bg-danger me-2">Nova</span>
                                                @endif
                                <a href="{{ route('messages.thread', $thread['threadId']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-comment fa-3x text-muted mb-3"></i>
                <h3>Nenhuma mensagem encontrada</h3>
                <p class="text-muted">Não há mensagens no sistema.</p>
                            </div>
                        @endif
                    </div>
                </div>
@endsection 