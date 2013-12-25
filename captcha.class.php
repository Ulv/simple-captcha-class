<?php

/**
 * класс "капча"
 *
 * Использование:
 * <code>
 * $captcha = new captcha(__DIR__ . '/assets/');
 * $_SESSION['security_code'] = $captcha->getCode();
 *
 * header('Content-Type: image/png');
 * $captcha->generateImage();
 * </code>
 *
 * и дальше проверка соответствия введенного значения в input формы и $_SESSION['security_code']
 *
 * PHP version 5
 *
 * @category Website
 * @package  Application
 * @author   Vladimir Chmil <vladimir.chmil@gmail.com>
 * @license  http://mit-license.org/ MIT license
 * @link     http://xxx
 */
class captcha
{
    /**
     * @var array массив шрифтов. В данной версии исп. только один шрифт
     */
    private $fonts;
    /**
     * @var string путь к шрифтам
     */
    private $assetsPath;

    /**
     * @var int мин. кол-во символов
     */
    private $minLength = 4;
    /**
     * @var int макс. кол-во символов
     */
    private $maxLength = 4;
    /**
     * @var int мин. размер шрифта
     */
    private $minFontSize = 28;
    /**
     * @var int макс. размер шрифта
     */
    private $maxFontSize = 28;

    /**
     * @var int высота картинки
     */
    private $imgH = 50;
    /**
     * @var int ширина картинки
     */
    private $imgW = 180;

    /**
     * @var string набор символов. Формируется в конструкторе
     */
    private $characters;
    /**
     * @var код капчи (генерируется в $this->generateCode())
     */
    private $code;

    /**
     * @param string $assetsPath путь к шрифтам
     */
    public function __construct($assetsPath = ".")
    {
        if (! $this->checkGD()) {
            throw new Exception('GD не установлен');
        }

        if (! is_readable($assetsPath)) {
            throw new Exception('Невозможно читать файлы из ' . $assetsPath);
        }

        $this->assetsPath = realpath($assetsPath);

        if ($this->assetsPath === false) {
            throw new Exception(sprintf('Путь %s не существует!', $assetsPath));
        }

        $this->fonts = glob($this->assetsPath . '/*.ttf');

        $this->characters = implode(array_merge(range('A', 'Z'), range('1', '9')));
    }

    /**
     * проеряет, установлен ли GD
     *
     * @return bool
     */
    private function checkGD()
    {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    /**
     * генерация кода капчи
     *
     * @return mixed
     */
    private function generateCode()
    {
        srand(microtime() * 100);

        $length = rand($this->minLength, $this->maxLength);
        while (strlen($this->code) < $length) {
            $this->code .= substr($this->characters, rand() % (strlen($this->characters)), 1);
        }

        return $this->code;
    }

    /**
     * возвращает код капчи. Если он еще не сгенерирован - генерируется
     *
     * @return mixed
     */
    public function getCode()
    {
        if (empty($this->code)) {
            $this->generateCode();
        }

        return $this->code;
    }

    /**
     * генерирует картинку капчи с искажениями
     */
    public function generateImage()
    {
        $img = imagecreatetruecolor($this->imgW, $this->imgH);
        $bgc = imagecolorallocate($img, 255, 255, 255);

        imagefilledrectangle($img, 0, 0, $this->imgW, $this->imgH, $bgc);

        $font_size = rand($this->minFontSize, $this->maxFontSize);

        $text_pos_x_min = 0;
        $text_pos_x_max = $this->imgH - $this->maxFontSize;
        $text_pos_x     = rand($text_pos_x_min, $text_pos_x_max);
        $text_pos_y_min = ($this->imgH - $this->maxFontSize) + 20;
        $text_pos_y_max = ($this->imgH - $this->maxFontSize) + 10;
        $text_pos_y     = rand($text_pos_y_min, $text_pos_y_max);
        $angle_shadow   = mt_rand(- 10, 10);
        $font           = $this->fonts[0];

        $shadow_offset_x = mt_rand(- 10, 10);
        $shadow_offset_y = mt_rand(- 10, 10);

        for ($i = 0; $i < 5; $i ++) {
            $elx1 = mt_rand(- 20, $this->imgW);
            $ely1 = mt_rand(- 20, $this->imgH);
            $elx2 = mt_rand(- 20, $this->imgW);
            $ely2 = mt_rand(- 20, $this->imgH);

            $color        = imagecolorallocate($img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            $color_darken = imagecolorallocate($img, mt_rand(20, 50), mt_rand(20, 50), mt_rand(20, 50));
            if ($i % 2) {
                imagefilledrectangle($img, round(min($elx1, $elx2) - $i), round(min($ely1, $ely2) - $i), round(max($elx1, $elx2) + $i), round(max($ely1, $ely2) + $i), $color);
            } else {
                imageline($img, $elx1, $ely1, $elx2, $ely2, $color_darken);
            }
        }

        imagelayereffect($img, IMG_EFFECT_OVERLAY);

        $shadow_color = imagecolorallocate($img, mt_rand(240, 255), mt_rand(240, 255), mt_rand(240, 255));
        for ($i = 0; $i < strlen($this->getCode()); $i ++) {
            imagettftext($img, $font_size, $angle_shadow,
                         $text_pos_x + $shadow_offset_x + $i * mt_rand(20, 40),
                         $text_pos_y + $shadow_offset_y + $i * mt_rand(- 5, 5),
                         $shadow_color, $font, $this->getCode()[$i]);

        }

        for ($i = 0; $i < strlen($this->getCode()); $i ++) {
            $angle = mt_rand(- 20, 20);
            $color = imagecolorallocate($img, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
            imagettftext($img, $font_size * mt_rand(10, 12) / 10, $angle,
                         $text_pos_x + $i * mt_rand(30, 35),
                         $text_pos_y + $i * mt_rand(- 5, 5)
                , $color, $font,
                         $this->getCode()[$i]);
        }

        imagepng($img);
        imagedestroy($img);
    }
}