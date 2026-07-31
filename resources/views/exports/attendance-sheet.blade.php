<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { margin: 0 0 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 2px 3px; text-align: center; }
        th { background: #f0f0f0; font-size: 7px; }
        .draft-watermark {
            position: fixed;
            top: 40%;
            left: 20%;
            font-size: 72px;
            color: rgba(200, 0, 0, 0.15);
            transform: rotate(-30deg);
            z-index: -1;
        }
        .header { margin-bottom: 12px; }
    </style>
</head>
<body>
    @if($isDraft)
        <div class="draft-watermark">DRAFT</div>
    @endif
    <div class="header">
        {!! $header !!}
    </div>
    {!! $tableHtml !!}
    {!! $signatureHtml !!}
</body>
</html>
