<?php include __DIR__ . '/../header.php'; ?>

<style>
        /* Базові стилі для фону */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            background-color: #faf8f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        body {
            padding-top: 86px;
        }

        /* Контейнер для карти */
        .map-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 86px);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Стилізація самого вікна з картою */
        iframe {
            width: 100%;
            height: 100%;
            border: none;
            z-index: 2;
        }

        /* Текст завантаження (якщо інтернет повільний) */
        .loader {
            position: absolute;
            color: #ffcc00;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            z-index: 1;
        }

        /* Плашка зверху (опціонально, для краси) */
        .header-overlay {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            padding: 10px 25px;
            border-radius: 30px;
            border: 1px solid #444;
            color: white;
            z-index: 10;
            pointer-events: none; /* Щоб не заважала клікати по карті */
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        .map-open-link {
            position: absolute;
            top: 15px;
            right: 20px;
            z-index: 10;
            padding: 10px 16px;
            border-radius: 6px;
            background: #006ede;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 640px) {
            body {
                padding-top: 72px;
                overflow: hidden;
            }

            .map-container {
                height: calc(100dvh - 72px);
                min-height: 0;
            }

            .header-overlay {
                top: 10px;
                left: 10px;
                transform: none;
                padding: 7px 10px;
                font-size: 11px;
                letter-spacing: .8px;
            }

            .map-open-link {
                top: 10px;
                right: 10px;
                padding: 7px 10px;
                font-size: 12px;
                border-radius: 5px;
            }

            .loader {
                max-width: 80%;
                text-align: center;
                font-size: 12px;
                letter-spacing: 1px;
            }
        }
    </style>

    <div class="header-overlay">ATCRAFT WORLD MAP</div>
    <a class="map-open-link"
       href="/map/proxy/#atyc:-353:0:519:796:0:0.03:0:0:perspective"
       target="_blank" rel="noopener">Открыть карту отдельно</a>

    <div class="map-container">
        <div class="loader">Завантаження світу...</div>
        <iframe 
            src="/map/proxy/#atyc:-353:0:519:796:0:0.03:0:0:perspective" 
            allowfullscreen>
        </iframe>
    </div>

