<?php

namespace App\Http\Controllers;

use App\Jobs\SearchYoutubeAndVerifyJob;
use App\Models\Product;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('videoCandidates')->latest();

        if ($request->filled('search')) {
            $query->where('denumire', 'like', '%' . $request->search . '%');
        }
        if ($request->has('fara_video')) {
            $query->whereNull('youtube_url');
        }

        $products = $query->paginate(15)->appends($request->all());

        $stats = [
            'total'    => Product::count(),
            'verified' => Product::where('ai_verified', true)->count(),
            'no_video' => Product::whereNull('youtube_url')->count(),
        ];

        return view('products.index', compact('products', 'stats'));
    }

    public function startSearch(Product $product)
    {
        SearchYoutubeAndVerifyJob::dispatch($product);

        $pending   = session('pending_jobs', []);
        $pending[] = $product->id;
        session(['pending_jobs' => $pending]);

        if (request()->expectsJson()) {
            return response()->json(['status' => 'queued', 'product_id' => $product->id]);
        }

        return back()->with('success', "✅ Job pornit pentru: {$product->denumire}");
    }

    public function acceptManual(Request $request, Product $product)
    {
        $request->validate([
            'video_id' => 'required|string',
            'title'    => 'required|string',
        ]);

        $product->update([
            'youtube_url'      => 'https://www.youtube.com/watch?v=' . $request->video_id,
            'youtube_video_id' => $request->video_id,
            'youtube_found_at' => now(),
            'ai_verified'      => false,
            'ai_explanation'   => 'Selectat manual: ' . $request->title,
        ]);

        return back()->with('success', "✅ Video acceptat manual pentru: {$product->denumire}");
    }

    public function importForm()
    {
        return view('products.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getPathname());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            if (empty($rows)) {
                return back()->withErrors(['file' => 'Fișierul este gol.']);
            }

            // Prima linie = header
            $header   = array_map('strtolower', array_map('trim', array_values($rows[1])));
            $imported = 0;

            foreach (array_slice($rows, 1) as $row) {
                $data      = array_combine($header, array_values($row));
                $denumire  = $data['denumire'] ?? $data['name'] ?? null;
                $categorie = $data['categorie'] ?? $data['category'] ?? 'Necunoscut';

                if (empty(trim((string) $denumire))) continue;

                Product::firstOrCreate(
                    ['denumire' => trim($denumire)],
                    ['categorie' => trim($categorie)]
                );
                $imported++;
            }

            return back()->with('success', "✅ {$imported} produse importate cu succes!");
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Eroare la import: ' . $e->getMessage()]);
        }
    }
}
