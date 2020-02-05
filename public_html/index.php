<?php

require  '../vendor/autoload.php';

use libphonenumber\PhoneNumberUtil;

$phoneNumberUtil = PhoneNumberUtil::getInstance();
$supportedRegions = $phoneNumberUtil->getSupportedRegions();

// данные телефонных номеров
$data = [];

// повторяющиеся телефонные номера
// у них одинаковый dialCode и placeholder
// не работаем с ними дальше
$repeatDialCodes = [];

for($i = 0, $count = count($supportedRegions); $i < $count; $i++) {
    $phoneMetadata = $phoneNumberUtil->getMetadataForRegion($supportedRegions[$i]);

    // цифровой код (например "7" для России)
    $dialCode = $phoneMetadata->getCountryCode();

    // буквенный код (например "RU" для России)
    $countryCode = $phoneMetadata->getId();

    // часть мобильного номера телефона из примера, без кода страны
    $mobile = $phoneMetadata->getMobile();
    $exampleNumber = $mobile->getExampleNumber();

    if ($exampleNumber) {
        // номер телефона из примера, без пробелов и лишних символов
        $phone = "+{$dialCode}{$exampleNumber}";

        // объект с информацией о номере
        $phoneNumber = $phoneNumberUtil->parse($phone, $countryCode, null, true);

        // плейсхолдер номера
        $placeholder = $phoneNumberUtil->formatInOriginalFormat($phoneNumber, $countryCode);

        // иногда плейсхолдер не отформатирован и похож на номер
        // или плейсхолдера нет, но такое мне не попадалось
        if ($phone === $placeholder || !$placeholder) {
            // получаем другой формат плейсхолдера
            $placeholder = $phoneNumberUtil->formatNumberForMobileDialing($phoneNumber, $countryCode, true);

            // плейсхолдер не начинается с "+", создаем свой плейсхолдер
            if (!$placeholder || substr($placeholder, 0, 1) !== '+') {
                $placeholder = "+{$dialCode} {$exampleNumber}";
            }
        }

        if (!array_key_exists($dialCode, $data)) {
            $data[$dialCode] = [
                'dialCode' => $dialCode,
                'countryCode' => $countryCode,
                // иногда в плейсхолдере попадаются ".", меняем их на " "
                'placeholder' => preg_replace('/\./', ' ', $placeholder),
            ];
        } else {
            $repeatDialCodes[$i] = [
                'dialCode' => $dialCode,
                'countryCode' => $countryCode,
                'placeholder' => preg_replace('/\./', ' ', $placeholder),
            ];
        }
    }
}


// выводим данные в JSON для JS
foreach ($data as $key => $value) {
    echo "<pre>";

    echo '{';
    echo 'dialCode: "' . (int) $value['dialCode'] . '",';
    echo 'countryCode: "' . strtolower($value['countryCode']) . '",';
    echo 'placeholder: "' . $value['placeholder'] . '",';

    echo 'mask: [';
    // разбиваем плейсхолдер посимвольно
    $mask = str_split($value['placeholder'], 1);
    for ($i = 0, $count = count($mask); $i < $count; $i++) { 
        $symbol = '';
        switch ($mask[$i]) {
            case '+':
                $symbol = '"+"';
                break;

            case ' ':
                $symbol = '" "';
                break;

            case '-':
                $symbol = '"-"';
                break;

            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
                $symbol = '/\d/';
                break;
        }

        if ($symbol) {
            echo $symbol;
            echo ',';
        }
    }
    echo ']';

    echo '},';
    
    echo "</pre>";
}