<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <?= view('waiver/default_theme/document_styles') ?>

    <style>
        @page {
            margin: 0.55in;
        }

        body {
            margin: 0;
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.35;
        }

        .waiver-document-pdf .waiver-card {
            border: 1px solid #aaa;
            padding: 14px;
            margin-bottom: 14px;
        }

        .waiver-document-pdf h2 {
            font-size: 13pt;
            margin-top: 14px;
            margin-bottom: 7px;
        }

        .waiver-document-pdf p {
            margin-top: 7px;
            margin-bottom: 7px;
        }

        .waiver-document-pdf .pdf-checkbox {
            font-size: 15pt;
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <?php
    echo view('waiver/default_theme/document', array_merge(
        $replacementMap,
        [
            'render_mode' => 'pdf',
            'signature_png' => $signature_png,
            'initials_png' => $initials_png
            // 'age_acknowledged' => $age_acknowledged,
            // 'acknowledged' => $acknowledged,
        ]
    ));
    ?>

</body>

</html>