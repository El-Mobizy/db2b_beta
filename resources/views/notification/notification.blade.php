<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Louer Meublée</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: rgb(240, 240, 245);
            margin: 0;
        }

        .container {
            width: 650px;
            height: 450px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logo {
            width: 150px;
            height: 150px;
            margin-bottom: -10px;
        }

        .separator {
            border-top: 1px solid #c2c2c2; /* Gris léger pour le séparateur */
            margin: 10px 0;
        }

        .email-title {
            color: #1e272e;
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: bold; /* Ajout d'audace au titre */
        }

        .email-body {
            font-size: 18px; /* Taille de police réduite pour un meilleur équilibre */
            color: #0066cc; /* Bleu foncé pour le corps du message */
            margin: 20px 0;
            text-align: left; /* Alignement à gauche pour le corps du texte */
        }

        .email-body span {
            padding: 10px; /* Ajout d'espace autour du texte */
        }

        .footer {
            color: #0066cc; /* Bleu pour le pied de page */
            font-size: 16px; /* Taille de police réduite pour le pied de page */
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <hr class="separator">
        <div class="email-title">{{ $mail['title'] }}</div>
        <div class="email-body"><span>{{ $mail['body'] }}</span></div>
        <div class="footer">DB2B</div>
    </div>
</body>
</html>
