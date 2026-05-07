@php
    $content = $article->content;
    $previewText = '';
    
    try {
        $blocks = json_decode($content, true);
        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if ($block['type'] === 'RICH_TEXT') {
                    $previewText .= strip_tags($block['data']['content']) . ' ';
                }
            }
        } else {
            $previewText = strip_tags($content);
        }
    } catch (\Exception $e) {
        $previewText = strip_tags($content);
    }
    
    $previewText = Str::limit($previewText, 250);
@endphp

<x-mail::message>
<div style="text-align: center; margin-bottom: 20px;">
    <h2 style="color: #0835A8; margin-bottom: 5px; font-size: 24px;">Chaudri CPA</h2>
    <p style="color: #646464; font-size: 14px; margin-top: 0;">Your Trusted Financial Partner</p>
</div>

@if($article->banner_image)
<div style="margin-bottom: 25px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
    <img src="{{ $article->banner_image }}" alt="{{ $article->title }}" style="width: 100%; height: auto; display: block;">
</div>
@endif

# {{ $article->title }}

<div style="color: #4a5568; line-height: 1.6; margin-bottom: 25px; font-size: 16px;">
    {{ $previewText }}
</div>

<x-mail::button :url="'https://chaudricpa.com/blog/' . $article->id" color="primary">
Read Full Article
</x-mail::button>

<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center;">
    <p style="font-size: 14px; color: #718096; margin-bottom: 5px;">
        Stay ahead with the latest financial insights.
    </p>
    <p style="font-size: 12px; color: #a0aec0;">
        © {{ date('Y') }} Chaudri CPA. All rights reserved.
    </p>
</div>

<hr style="border: none; border-top: 1px solid #edf2f7; margin: 30px 0;">

<div style="text-align: center;">
    <p style="font-size: 11px; color: #cbd5e0;">
        You are receiving this email because you subscribed to the Chaudri CPA newsletter.<br>
        <a href="{{ $unsubscribeUrl }}" style="color: #0835A8; text-decoration: underline;">Unsubscribe from this list</a>
    </p>
</div>
</x-mail::message>
