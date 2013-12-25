# Описание

Очень простой класс для генерации капчи.

Использование:

    $captcha = new captcha(__DIR__ . '/assets/');
    $_SESSION['security_code'] = $captcha->getCode();
    header('Content-Type: image/png');
    $captcha->generateImage();

и дальше проверка соответствия введенного значения в input формы и $_SESSION['security_code']

Автор Vladimir Chmil <vladimir.chmil@gmail.com>
