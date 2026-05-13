<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VideoAI Matcher — Produse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .status-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 10px; border-radius:9999px; font-size:0.75rem; font-weight:500; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">🎬 VideoAI Matcher</h1>
            <p class="text-sm text-gray-500 mt-1">Caută și verifică automat trailerele produselor</p>
        </div>
        <a href="{{ route('products.import.form') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            ⬆ Import Excel
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 mb-4 text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 mb-4 text-sm">
        {{ session('error') }}
    </div>
    @endif

    {{-- Filtre --}}
    <form method="GET" action="{{ route('products.index') }}"
          class="bg-white border border-gray-200 rounded-xl px-5 py-4 mb-6 flex flex-wrap gap-3 items-end shadow-sm">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-medium text-gray-600 mb-1">Caută după denumire</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ex: Elden Ring..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
        <div class="flex items-center gap-2 pb-1">
            <input type="checkbox" name="fara_video" id="fara_video" value="1"
                   {{ request('fara_video') ? 'checked' : '' }}
            class="w-4 h-4 rounded border-gray-300 text-indigo-600">
            <label for="fara_video" class="text-sm text-gray-700 cursor-pointer">Doar fără video</label>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Filtrează
            </button>
            <a href="{{ route('products.index') }}"
               class="border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg transition">
                Reset
            </a>
        </div>
    </form>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total produse</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">AI Verificat</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['verified'] }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl px-5 py-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Fără video</p>
            <p class="text-2xl font-bold text-orange-500 mt-1">{{ $stats['no_video'] }}</p>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide w-12">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Denumire</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Categorie</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Video</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">AI Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Acuratețe</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Găsit la</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Acțiuni</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($products as $product)
            <tr class="hover:bg-gray-50 transition" id="row-{{ $product->id }}">
                <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $product->id }}</td>
                <td class="px-4 py-3 font-medium text-gray-900">
                        <span class="truncate block max-w-[260px]" title="{{ $product->denumire }}">
                            {{ $product->denumire }}
                        </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $product->categorie }}</td>
                <td class="px-4 py-3">
                    @if($product->youtube_url)
                    <a href="{{ $product->youtube_url }}" target="_blank"
                       class="text-indigo-600 hover:text-indigo-800 underline text-xs font-medium">
                        ▶ Vezi video
                    </a>
                    @else
                    <span class="text-gray-400 text-xs">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($product->ai_verified)
                    <span class="status-pill bg-green-100 text-green-700">✓ Verificat</span>
                    @elseif($product->ai_explanation)
                    <span class="status-pill bg-red-100 text-red-600">✗ Respins</span>
                    @else
                    <span class="status-pill bg-gray-100 text-gray-500">— Necăutat</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($product->ai_accuracy !== null)
                    <div class="flex items-center gap-2">
                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $product->ai_accuracy >= 75 ? 'bg-green-500' : 'bg-red-400' }}"
                                 style="width: {{ min($product->ai_accuracy, 100) }}%"></div>
                        </div>
                        <span class="text-xs font-mono text-gray-600">{{ number_format($product->ai_accuracy, 0) }}%</span>
                    </div>
                    @else
                    <span class="text-gray-400 text-xs">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-gray-400">
                    {{ $product->youtube_found_at ? $product->youtube_found_at->format('d.m.Y H:i') : '—' }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('products.search', $product) }}"
                              class="search-form" data-product-id="{{ $product->id }}">
                            @csrf
                            <button type="submit"
                                    class="text-xs font-medium px-3 py-1.5 rounded-lg transition
                                               {{ $product->youtube_url
                                                  ? 'border border-gray-300 hover:bg-gray-100 text-gray-600'
                                                  : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                                {{ $product->youtube_url ? '🔄 Re-caută' : '🔍 Caută video' }}
                            </button>
                        </form>
                        <button onclick="toggleModal({{ $product->id }})"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-600 transition">
                            👁 Detalii
                        </button>
                    </div>
                </td>
            </tr>

            {{-- Rând expandabil cu candidați --}}
            <tr id="modal-{{ $product->id }}" class="hidden bg-indigo-50">
                <td colspan="8" class="px-6 py-4">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-semibold text-gray-800">📋 {{ $product->denumire }}</h3>
                        <button onclick="toggleModal({{ $product->id }})"
                                class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
                    </div>

                    @if($product->ai_explanation)
                    <div class="bg-white border border-gray-200 rounded-lg p-3 mb-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Verdict AI</p>
                        <p class="text-sm text-gray-700">{{ $product->ai_explanation }}</p>
                        @if($product->ai_accuracy !== null)
                        <p class="text-xs text-gray-400 mt-1">Acuratețe: <strong>{{ $product->ai_accuracy }}%</strong></p>
                        @endif
                    </div>
                    @endif

                    @php $candidates = $product->videoCandidates; @endphp
                    @if($candidates->count() > 0)
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Candidați ({{ $candidates->count() }})</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach($candidates as $c)
                        <div class="bg-white border {{ $product->youtube_video_id === $c->video_id ? 'border-green-400 ring-1 ring-green-300' : 'border-gray-200' }} rounded-lg p-3">
                            <p class="text-sm font-medium text-gray-800 mb-1 leading-snug">{{ $c->title }}</p>
                            <p class="text-xs text-gray-500 mb-2">📺 {{ $c->channel }}</p>
                            @if($c->description_snippet)
                            <p class="text-xs text-gray-400 mb-2 line-clamp-2">{{ $c->description_snippet }}</p>
                            @endif
                            <div class="flex items-center justify-between mt-2">
                                <a href="https://www.youtube.com/watch?v={{ $c->video_id }}"
                                   target="_blank"
                                   class="text-xs text-indigo-600 hover:underline font-medium">▶ Deschide</a>
                                @if($product->youtube_video_id !== $c->video_id)
                                <form method="POST" action="{{ route('products.accept', $product) }}">
                                    @csrf
                                    <input type="hidden" name="video_id" value="{{ $c->video_id }}">
                                    <input type="hidden" name="title" value="{{ $c->title }}">
                                    <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium">
                                        ✓ Accept manual
                                    </button>
                                </form>
                                @else
                                <span class="text-xs text-green-600 font-semibold">✓ Selectat</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-sm text-gray-400 italic">Niciun candidat salvat încă. Apasă "Caută video" pentru a porni căutarea.</p>
                    @endif
                </td>
            </tr>

            @empty
            <tr>
                <td colspan="8" class="px-4 py-16 text-center text-gray-400">
                    <p class="text-4xl mb-3">📭</p>
                    <p class="font-medium text-gray-600">Niciun produs găsit</p>
                    <p class="text-sm mt-1">Importă un fișier Excel sau ajustează filtrele</p>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginare --}}
    <div class="mt-5">
        {{ $products->links() }}
    </div>
</div>

<script>
    function toggleModal(id) {
        const row = document.getElementById('modal-' + id);
        row.classList.toggle('hidden');
    }

    document.querySelectorAll('.search-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const btn = this.querySelector('button');
            const statusCell = document.querySelector('#row-' + productId + ' td:nth-child(5)');

            btn.disabled = true;
            btn.textContent = '⏳ Se procesează...';
            statusCell.innerHTML = '<span class="status-pill bg-yellow-100 text-yellow-700">⏳ Pending...</span>';

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            }).then(() => {
                btn.disabled = false;
                btn.textContent = '🔄 Re-caută';
            }).catch(() => {
                btn.disabled = false;
                btn.textContent = '⚠ Eroare';
            });
        });
    });
</script>
</body>
</html>
