<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

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

        .waiver-card {
            border: 1px solid #aaa;
            padding: 14px;
            margin-bottom: 14px;
        }

        .logo-card {
            width: 100%;
            display: table;
        }

        .logo-column,
        .title-column {
            display: table-cell;
            vertical-align: middle;
        }

        .logo-column {
            width: 30%;
            text-align: center;
        }

        .title-column {
            width: 70%;
        }

        .waiver-logo {
            max-width: 145px;
            max-height: 100px;
        }

        .centered {
            text-align: center;
        }

        .w3-center {
            text-align: center;
        }

        .w3-image {
            max-width: 100%;
            height: auto;
        }

        h2 {
            font-size: 13pt;
            margin-top: 14px;
            margin-bottom: 7px;
        }

        p {
            margin-top: 7px;
            margin-bottom: 7px;
        }

        .participant-name {
            font-size: 15pt;
            padding: 8px 0 12px 12px;
        }

        .initials-panel,
        .signature-panel {
            min-height: 55px;
            padding: 6px 10px;
            margin: 8px 0 12px 0;
            border-left: 5px solid #3178c6;
            background: #f3f3f3;
        }

        .initials-image {
            max-width: 140px;
            max-height: 70px;
        }

        .signature-image {
            max-width: 360px;
            max-height: 130px;
        }

        .pdf-checkbox {
            font-size: 15pt;
            vertical-align: middle;
        }

        .consent-row {
            width: 100%;
            display: table;
        }

        .consent-checkbox,
        .consent-text {
            display: table-cell;
            vertical-align: top;
        }

        .consent-checkbox {
            width: 28px;
            font-size: 15pt;
        }

        .consent-text {
            width: auto;
        }

        .lm-red {
            color: #c00000;
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