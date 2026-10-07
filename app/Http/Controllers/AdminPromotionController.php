<?php

namespace App\Http\Controllers;

use App\Models\SponsoredAd;
use App\Support\AuditLogger;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminPromotionController extends Controller
{
    public function index(): View
    {
        $this->ensureAdmin();

        $promotions = SponsoredAd::query()
            ->orderBy('order')
            ->latest()
            ->get();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
        ]);
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('admin.promotions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $type = $request->input('type', 'image');
        $mediaRules = ['required', 'file', 'max:30720'];

        if ($type === 'image') {
            $mediaRules[] = 'mimes:jpg,jpeg,png,webp';
        } else {
            $mediaRules[] = 'mimes:mp4,webm,mov,ogg,m4v';
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:image,video'],
            'media' => $mediaRules,
            'link_url' => ['nullable', 'url', 'max:500'],
            'caption' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $file = $request->file('media');

        if ($type === 'image') {
            $mediaPath = ImageOptimizer::store($file, 'promotions');
        } else {
            $mediaPath = $file->store('promotions', 'public');
        }

        $ad = SponsoredAd::query()->create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'media_path' => $mediaPath,
            'link_url' => $validated['link_url'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'order' => (int) ($validated['order'] ?? 0),
            'status' => $validated['status'],
        ]);

        AuditLogger::log('PROMOTION_CREATED', 'SponsoredAd', $ad->id, ['title' => $ad->title, 'type' => $ad->type]);

        return redirect()->route('admin.promotions.index')->with('status', 'Iklan sponsor berhasil ditambahkan.');
    }

    public function edit(SponsoredAd $promotion): View
    {
        $this->ensureAdmin();

        return view('admin.promotions.edit', [
            'promotion' => $promotion,
        ]);
    }

    public function update(Request $request, SponsoredAd $promotion): RedirectResponse
    {
        $this->ensureAdmin();

        $type = $request->input('type', $promotion->type);
        $mediaRules = ['nullable', 'file', 'max:30720'];

        if ($type === 'image') {
            $mediaRules[] = 'mimes:jpg,jpeg,png,webp';
        } else {
            $mediaRules[] = 'mimes:mp4,webm,mov,ogg,m4v';
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:image,video'],
            'media' => $mediaRules,
            'link_url' => ['nullable', 'url', 'max:500'],
            'caption' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data = [
            'title' => $validated['title'],
            'type' => $validated['type'],
            'link_url' => $validated['link_url'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'order' => (int) ($validated['order'] ?? 0),
            'status' => $validated['status'],
        ];

        if ($request->hasFile('media')) {
            $file = $request->file('media');

            if ($validated['type'] === 'image') {
                $newPath = ImageOptimizer::store($file, 'promotions');
            } else {
                $newPath = $file->store('promotions', 'public');
            }

            if ($promotion->media_path && Storage::disk('public')->exists($promotion->media_path)) {
                Storage::disk('public')->delete($promotion->media_path);
            }

            $data['media_path'] = $newPath;
        }

        $promotion->update($data);

        AuditLogger::log('PROMOTION_UPDATED', 'SponsoredAd', $promotion->id, ['title' => $promotion->title, 'status' => $promotion->status]);

        return redirect()->route('admin.promotions.index')->with('status', 'Iklan sponsor berhasil diperbarui.');
    }

    public function toggle(SponsoredAd $promotion): RedirectResponse
    {
        $this->ensureAdmin();

        $newStatus = $promotion->status === 'active' ? 'inactive' : 'active';
        $promotion->update(['status' => $newStatus]);

        AuditLogger::log('PROMOTION_STATUS_UPDATED', 'SponsoredAd', $promotion->id, ['status' => $newStatus]);

        return back()->with('status', 'Status iklan berhasil diubah menjadi '.($newStatus === 'active' ? 'Aktif' : 'Nonaktif').'.');
    }

    public function destroy(SponsoredAd $promotion): RedirectResponse
    {
        $this->ensureAdmin();

        if ($promotion->media_path && Storage::disk('public')->exists($promotion->media_path)) {
            Storage::disk('public')->delete($promotion->media_path);
        }

        $title = $promotion->title;
        $id = $promotion->id;
        $promotion->delete();

        AuditLogger::log('PROMOTION_DELETED', 'SponsoredAd', $id, ['title' => $title]);

        return redirect()->route('admin.promotions.index')->with('status', 'Iklan sponsor berhasil dihapus.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
