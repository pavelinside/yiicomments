<?php
class tifTag {
  const TIFF_ENDIAN_BIG = 0;
  const TIFF_ENDIAN_LITTLE = 1;

  const UNPACK_TYPE_BYTE = 0;
  const UNPACK_TYPE_SHORT = 1;
  const UNPACK_TYPE_LONG = 2;
  const UNPACK_TYPE_RATIONAL = 3;

  const TIFF_FIELD_TYPE_BYTE = 1;
  const TIFF_FIELD_TYPE_ASCII = 2;
  const TIFF_FIELD_TYPE_SHORT = 3;
  const TIFF_FIELD_TYPE_LONG = 4;
  const TIFF_FIELD_TYPE_RATIONAL = 5;
  const TIFF_FIELD_TYPE_SBYTE = 6;
  const TIFF_FIELD_TYPE_UNDEFINED = 7;
  const TIFF_FIELD_TYPE_SSHORT = 8;
  const TIFF_FIELD_TYPE_SLONG = 9;
  const TIFF_FIELD_TYPE_SRATIONAL = 10;
  const TIFF_FIELD_TYPE_FLOAT = 11;
  const TIFF_FIELD_TYPE_DOUBLE = 12;

  const TIFF_COMPRESSION_UNCOMPRESSED = 1;
  const TIFF_COMPRESSION_CCITT1D = 2;
  const TIFF_COMPRESSION_GROUP_3_FAX = 3;
  const TIFF_COMPRESSION_GROUP_4_FAX = 4;
  const TIFF_COMPRESSION_LZW = 5;
  const TIFF_COMPRESSION_JPEG = 6;
  const TIFF_COMPRESSION_FLATE = 8; // extension_loaded('zlib')  $o=@gzuncompress($data)

  const TIFF_PHOTOMETRIC_INTERPRETATION_WHITE_IS_ZERO = 0;
  const TIFF_PHOTOMETRIC_INTERPRETATION_BLACK_IS_ZERO = 1;
  const TIFF_PHOTOMETRIC_INTERPRETATION_RGB = 2;
  const TIFF_PHOTOMETRIC_INTERPRETATION_RGB_INDEXED = 3;
  const TIFF_PHOTOMETRIC_INTERPRETATION_CMYK = 5;
  const TIFF_PHOTOMETRIC_INTERPRETATION_YCBCR = 6;
  const TIFF_PHOTOMETRIC_INTERPRETATION_CIELAB = 8;

  protected $_tags = [];

  protected $_imageProperties;
  protected $_imageDataBytes;

  protected $_width;
  protected $_height;
  protected $_endianType;
  protected $_fileSize;
  protected $_bitsPerSample;
  protected $_compression;
  protected $_colorCode;
  protected $_whiteIsZero;
  protected $_blackIsZero;
  protected $_colorSpace;
  protected $_imageDataOffset;
  protected $_imageDataLength;

  /*
263	Threshholding	Вид преобразования серого в чёрное и белое для черно-белых изображений.
264	CellWidth	Количество колонок в матрице преобразования из серого в чёрное и белое.
265	CellHeight	Количество строк в матрице преобразования из серого в чёрное и белое.
266	FillOrder	Логический порядок битов в байте.
270	ImageDescription	Описание изображения.
271	Make	Производитель изображения.
272	Model	Модель или серийный номер.

274	Orientation	Ориентация изображения.
278	RowsPerStrip	Количество строк на полосу.


280	MinSampleValue	Минимальное значение, используемое компонентом.
281	MaxSampleValue	Максимальное значение, используемое компонентом.
282	XResolution	Количество пикселей в ResolutionUnit строки.
283	YResolution	Количество пикселей в ResolutionUnit столбца.
284	PlanarConfiguration	Метод хранения компонентов каждого пикселя.
288	FreeOffsets	Смещение в байтах к строке неиспользуемых байтов.
289	FreeByteCounts	Количество байтов в строке неиспользуемых байтов.
290	GrayResponseUnit	Разрешение данных, хранящихся в GrayResponseCurve.
291	GrayResponseCurve	Величина плотности серого.
296	ResolutionUnit	Разрешение данных, хранящихся в XResolution, YResolution.
305	Software	Имя и версия программного продукта.
306	DateTime	Дата и время создания изображения.
315	HostComputer	Компьютер и операционная система, использованные при создании изображения.
316	Artist	Имя создателя изображения.
320	ColorMap	Цветовая таблица для изображений, использующих палитру цветов.
338	ExtraSamples	Описание дополнительных компонентов.
33432	Copyright	Имя владельца прав на хранимое изображение.
   */

  //254	NewSubfileType	Метка для замены SubfileType, полезна, когда в одном TIFF файле хранится несколько изображений.
  //255	SubfileType	Тип данных, хранящихся в этом файле.
  const TIFF_TAG_IMAGE_WIDTH = 256;                 // 256 ImageWidth	Количество столбцов в изображении
  const TIFF_TAG_IMAGE_LENGTH = 257;                // 257	ImageLength	Количество строк в изображении
  // 258	BitsPerSample	Количество бит в компоненте, оно обычно одинаковое. Для RGB может быть 8 для всех компонентов — красного, зелёного и голубого, или 8,8,8 для каждого из компонентов.
  const TIFF_TAG_BITS_PER_SAMPLE = 258;
  const TIFF_TAG_COMPRESSION = 259;                 // 259 Compression	Используемый вид компрессии.
  const TIFF_TAG_PHOTOMETRIC_INTERPRETATION = 262;  // 262 PhotometricInterpretation	Используемая цветовая модель.
  const TIFF_TAG_STRIP_OFFSETS = 273;               // 273 StripOffsets	Смещение для каждой полосы изображения в байтах.
  const TIFF_TAG_SAMPLES_PER_PIXEL = 277;           // 277	SamplesPerPixel	Количество компонентов на пиксель.
  const TIFF_TAG_STRIP_BYTE_COUNTS = 279;           // 279	StripByteCounts	Количество байт на полосу после компрессии.

  const TIFF_COMPRESSION_FLATE_OBSOLETE_CODE = 32946;
  const TIFF_COMPRESSION_PACKBITS = 32773;

  /**
   * Byte unpacking function
   * Makes it possible to unpack bytes in one statement for enhanced logic readability.
   *
   * UTIF._binBE.readASCII(data, offset, 2); = fread($imageFile, 2);
   * bin.readUshort(data, offset); = $this->unpackBytes(self::UNPACK_TYPE_SHORT, fread($imageFile, 2));
   * bin.readUint(data, offset); = $this->unpackBytes(self::UNPACK_TYPE_LONG, fread($imageFile, 4));
   *
   * @param int $type
   * @param string $bytes
   * @return float|int|mixed
   * @throws Exception
   */
  protected function unpackBytes($type, $bytes) {
    if (!isset($this->_endianType)) {
      throw new \Exception(
        'The unpackBytes function can only be used after the endianness of the file is known'
      );
    }
    switch ($type) {
    case self::UNPACK_TYPE_BYTE:
      $format = 'C';
      $unpacked = unpack($format, $bytes);
      return $unpacked[1];
    break;
    case self::UNPACK_TYPE_SHORT:
      $format = ($this->_endianType == self::TIFF_ENDIAN_LITTLE) ? 'v' : 'n';
      $unpacked = unpack($format, $bytes);
      return $unpacked[1];
    break;
    case self::UNPACK_TYPE_LONG:
      $format = ($this->_endianType == self::TIFF_ENDIAN_LITTLE) ? 'V' : 'N';
      $unpacked = unpack($format, $bytes);
      return $unpacked[1];
    break;
    case self::UNPACK_TYPE_RATIONAL:
      $format = ($this->_endianType == self::TIFF_ENDIAN_LITTLE) ? 'V2' : 'N2';
      $unpacked = unpack($format, $bytes);
      return ($unpacked[1] / $unpacked[2]);
    break;
    }
  }

  /**
   * @param $imageFileName
   * @throws Exception
   */
  public function parse($imageFileName) {
    if (($handle= @fopen($imageFileName, 'rb')) === FALSE) {
      throw new \Exception("Can not open '$imageFileName' file for reading.");
    }
    $fileStats = fstat($handle);
    $this->_fileSize = $fileStats['size'];

    $this->_endianType = $this->getEndianType($handle);
    $this->checkTiff($handle);

    // байты 4—7 смещение в байтах от начала файла на первый каталог IFD (image file directory)
    // в последних 4 байтах IDF содержится указатель на следующий IFD
    $ifdOffset = $this->unpackBytes(self::UNPACK_TYPE_LONG, fread($handle, 4));

    // Tiff может содержать несколько IFD, каждая в начале содержит 2 байта - количество тегов + "специфические" 12 байт
    while ($ifdOffset > 0) {
      if (fseek($handle, $ifdOffset, SEEK_SET) == -1 || $ifdOffset + 2 >= $this->_fileSize) {
        throw new \Exception("Could not seek to the image file directory as indexed by the file. Likely cause is TIFF corruption. Offset: " . $ifdOffset);
      }

      $numDirEntries = $this->unpackBytes(self::UNPACK_TYPE_SHORT, fread($handle, 2));

      for ($dirEntryIdx = 1; $dirEntryIdx <= $numDirEntries; $dirEntryIdx++) {
        //  2 bytes (short) tag code; TIFF_TAG constants
        //  2 bytes (short) field type
        //  4 bytes (long) number of values, or value count.
        //  4 bytes (mixed) data if the data will fit into 4 bytes or an offset if the data is too large
        $tag = $this->unpackBytes(self::UNPACK_TYPE_SHORT, fread($handle, 2));
        $fieldType = $this->unpackBytes(self::UNPACK_TYPE_SHORT, fread($handle, 2));
        $valueCount = $this->unpackBytes(self::UNPACK_TYPE_LONG, fread($handle, 4));
        $offsetBytes = fread($handle, 4);

        switch ($fieldType) {
        case self::TIFF_FIELD_TYPE_BYTE:
        case self::TIFF_FIELD_TYPE_ASCII:
          $fieldLength = $valueCount;
        break;
        case self::TIFF_FIELD_TYPE_SHORT:
          $fieldLength = $valueCount * 2;
        break;
        case self::TIFF_FIELD_TYPE_LONG:
          $fieldLength = $valueCount * 4;
        break;
        case self::TIFF_FIELD_TYPE_RATIONAL:
          $fieldLength = $valueCount * 8;
        break;
        default:
          $fieldLength = $valueCount;
        }

        $arr = [];
        // 3
        if($fieldType === self::TIFF_FIELD_TYPE_SHORT){
          $arr = $this->getShort($handle, $valueCount, $offsetBytes);
        }

        // 1 7
        if($fieldType == self::TIFF_FIELD_TYPE_BYTE || $fieldType == self::TIFF_FIELD_TYPE_UNDEFINED) {
          $arr = $this->getByte($handle, $valueCount, $offsetBytes);
        }

        // 2
        if($fieldType == self::TIFF_FIELD_TYPE_ASCII) {
          $arr = $this->getASCII($handle, $valueCount, $offsetBytes);
        }

        // 4 13
        if($fieldType == self::TIFF_FIELD_TYPE_LONG || $fieldType == 13) {
          $arr = $this->getLong($handle, $valueCount, $offsetBytes);
        }

        // 5 10
        if($fieldType == self::TIFF_FIELD_TYPE_RATIONAL || $fieldType == self::TIFF_FIELD_TYPE_SRATIONAL) {
          $arr = $this->getRational($handle, $valueCount, $offsetBytes);
        }

        //echo "\r\n<br>"."$tag fT=$fieldType vC=$valueCount RES ";
        if($arr){
          $this->_tags[$tag] = $arr;
          //echo count($arr)."=".implode(',', array_slice($arr, 0, 10));
          //continue;
        }
      }

      $this->tagProcess();

      $ifdOffset = $this->unpackBytes(self::UNPACK_TYPE_LONG, fread($handle, 4));
    }

    if (!isset($this->_imageDataOffset) || !isset($this->_imageDataLength)) {
      throw new \Exception('TIFF: The image processed did not contain image data as expected.');
    }

    $this->readImageDataBytes($handle);

    fclose($handle);

    if (!isset($this->_width) || !isset($this->_height)) {
      throw new \Exception('Problem reading tiff file. Tiff is probably corrupt.');
    }

    $this->_imageProperties = [];
    $this->_imageProperties['endianType'] = $this->_endianType;
    $this->_imageProperties['fileSize'] = $this->_fileSize;
    $this->_imageProperties['bitDepth'] = $this->_bitsPerSample;
    $this->_imageProperties['TIFFcompressionType'] = $this->_compression;
    $this->_imageProperties['TIFFcolorCode'] = $this->_colorCode;
    $this->_imageProperties['TIFFwhiteIsZero'] = $this->_whiteIsZero;
    $this->_imageProperties['TIFFblackIsZero'] = $this->_blackIsZero;
    $this->_imageProperties['PDFcolorSpace'] = $this->_colorSpace;
    $this->_imageProperties['TIFFimageDataOffset'] = $this->_imageDataOffset;
    $this->_imageProperties['TIFFimageDataLength'] = $this->_imageDataLength;
    $this->_imageProperties['width'] = $this->_width;
    $this->_imageProperties['height'] = $this->_height;
  }

  /**
   * Object constructor
   */
  public function __construct() {

  }

  /**
   * Image width (defined in \Zend\Pdf\Resource\Image\AbstractImage)
   */
  public function getPixelWidth() {
    return $this->_width;
  }

  /**
   * Image height (defined in \Zend\Pdf\Resource\Image\AbstractImage)
   */
  public function getPixelHeight() {
    return $this->_height;
  }

  /**
   * Image properties (defined in \Zend\Pdf\Resource\Image\AbstractImage)
   */
  public function getProperties() {
    return $this->_imageProperties;
  }

  public function getImageDataBytes() {
    return $this->_imageDataBytes;
  }

  /**
   * @return array
   */
  public function getTags(): array {
    return $this->_tags;
  }

  /**
   * @param $handle
   * @param $valueCount
   * @param $offsetBytes
   * @return array
   */
  public function getShort($handle, $valueCount, $offsetBytes){
    $arr = [];
    // если значение меньше 4 байт, то вместо ссылки хранится значение
    if($valueCount >= 3){
      $refOffset = $this->unpackBytes(self::UNPACK_TYPE_LONG, $offsetBytes);
    }
    $fp = ftell($handle);
    for($j = 0; $j < $valueCount; $j++) {
      fseek($handle, ($valueCount < 3 ? $fp - 4 : $refOffset) + 2 * $j, SEEK_SET);
      $value = $this->unpackBytes(self::UNPACK_TYPE_SHORT, fread($handle, 2));
      $arr []= $value;
    }
    fseek($handle, $fp, SEEK_SET);
    return $arr;
  }

  /**
   * @param $handle
   * @param $valueCount
   * @param $offsetBytes
   * @return array
   */
  public function getByte($handle, $valueCount, $offsetBytes): array {
    if ($valueCount < 5) {
      $arr = [$this->unpackBytes(self::UNPACK_TYPE_BYTE, $offsetBytes)];
      return $arr;
    }

    $refOffset = $this->unpackBytes(self::UNPACK_TYPE_LONG, $offsetBytes);

    $fp = ftell($handle);
    fseek($handle, $refOffset, SEEK_SET);
    $stripOffsetsBytes = fread($handle, $valueCount);
    $arr = unpack('C*', $stripOffsetsBytes);
    fseek($handle, $fp, SEEK_SET);

    return $arr;
  }

  /**
   * порядок байтов
   * @param $handle
   * @throws Exception
   */
  public function getEndianType($handle): int {
    $byteOrderIndicator = fread($handle, 2);
    if ($byteOrderIndicator == 'II') {
      // прямой - Intel
      return self::TIFF_ENDIAN_LITTLE;
    } else if ($byteOrderIndicator == 'MM') {
      // обратный - Motorola
      return self::TIFF_ENDIAN_BIG;
    } else {
      throw new \Exception('Not a tiff file or Tiff corrupt. No byte order indication found');
    }
  }

  /**
   * байты 2 и 3 - идентификатор формата TIFF - число 42
   * @param $handle
   * @throws Exception
   */
  public function checkTiff($handle): void {
    $version = $this->unpackBytes(self::UNPACK_TYPE_SHORT, fread($handle, 2));
    if ($version != 42) {
      throw new \Exception('Not a tiff file or Tiff corrupt. Incorrect version number.');
    }
  }

  /**
   * @param $handle
   * @param $valueCount
   * @param $offsetBytes
   * @return array
   */
  public function getASCII($handle, $valueCount, $offsetBytes): array {
    if ($valueCount < 5) {
      $arr = [$this->unpackBytes(self::UNPACK_TYPE_LONG, $offsetBytes)];
      return $arr;
    }

    $refOffset = $this->unpackBytes(self::UNPACK_TYPE_LONG, $offsetBytes);
    $fp = ftell($handle);
    fseek($handle, $refOffset, SEEK_SET);
    $stripOffsetsBytes = fread($handle, $valueCount);
    $asciiArr = unpack('C*', $stripOffsetsBytes);
    $str = "";
    for ($i = 0, $len = count($asciiArr); $i < $len; $i++) {
      $str .= chr($asciiArr[$i]);
    }
    fseek($handle, $fp, SEEK_SET);

    return [$str];
  }

  /**
   *
   * @param $handle
   * @param $valueCount
   * @param $offsetBytes
   * @return array
 */
  public function getLong($handle, $valueCount, $offsetBytes): array {
    $arr = [];
    // если значение меньше 4 байт, то вместо ссылки хранится значение
    if ($valueCount >= 2) {
      $refOffset = $this->unpackBytes(self::UNPACK_TYPE_LONG, $offsetBytes);
    }
    $fp = ftell($handle);
    for ($j = 0; $j < $valueCount; $j++) {
      fseek($handle, ($valueCount < 2 ? $fp - 4 : $refOffset) + 2 * $j, SEEK_SET);
      $value = $this->unpackBytes(self::UNPACK_TYPE_LONG, fread($handle, 4));
      $arr [] = $value;
    }
    fseek($handle, $fp, SEEK_SET);
    return $arr;
  }

  /**
   * @param $handle
   * @param $offsetBytes
   * @return array
   */
  public function getRational($handle, $valueCount, $offsetBytes): array {
    $refOffset = $this->unpackBytes(self::UNPACK_TYPE_LONG, $offsetBytes);

    // Two LONGs: the first represents the numerator of a fraction; the second, the denominator.
    $fp = ftell($handle);
    fseek($handle, $refOffset, SEEK_SET);
    $numerator = $this->unpackBytes(self::UNPACK_TYPE_LONG, fread($handle, 4));
    fseek($handle, $refOffset + 4, SEEK_SET);
    $denominator = $this->unpackBytes(self::UNPACK_TYPE_LONG, fread($handle, 4));
    fseek($handle, $fp, SEEK_SET);
    return [$numerator, $denominator];
  }

  public function tagProcess(): void {
    if (isset($this->_tags[self::TIFF_TAG_BITS_PER_SAMPLE])) {
      $this->_bitsPerSample = $this->_tags[self::TIFF_TAG_BITS_PER_SAMPLE];
    }

    if (isset($this->_tags[self::TIFF_TAG_COMPRESSION])) {
      $this->_compression = $this->_tags[self::TIFF_TAG_COMPRESSION];
    }

    if (isset($this->_tags[self::TIFF_TAG_STRIP_OFFSETS])) {
      $this->_imageDataOffset = $this->_tags[self::TIFF_TAG_STRIP_OFFSETS];
    }

    if (isset($this->_tags[self::TIFF_TAG_STRIP_BYTE_COUNTS])) {
      $this->_imageDataLength = $this->_tags[self::TIFF_TAG_STRIP_BYTE_COUNTS];
    }

    if (isset($this->_tags[self::TIFF_TAG_IMAGE_WIDTH])) {
      $this->_width = $this->_tags[self::TIFF_TAG_IMAGE_WIDTH];
    }

    if (isset($this->_tags[self::TIFF_TAG_IMAGE_LENGTH])) {
      $this->_height = $this->_tags[self::TIFF_TAG_IMAGE_LENGTH];
    }

    if (isset($this->_tags[self::TIFF_TAG_IMAGE_LENGTH])) {
      $this->_height = $this->_tags[self::TIFF_TAG_IMAGE_LENGTH];
    }

    if (isset($this->_tags[self::TIFF_TAG_PHOTOMETRIC_INTERPRETATION])) {
      $this->_colorCode = $this->_tags[self::TIFF_TAG_PHOTOMETRIC_INTERPRETATION];
      $this->_whiteIsZero = FALSE;
      $this->_blackIsZero = FALSE;
      $this->_colorSpace = '';
      switch ($this->_colorCode) {
      case self::TIFF_PHOTOMETRIC_INTERPRETATION_WHITE_IS_ZERO:
        $this->_whiteIsZero = TRUE;
        $this->_colorSpace = 'DeviceGray';
      break;
      case self::TIFF_PHOTOMETRIC_INTERPRETATION_BLACK_IS_ZERO:
        $this->_blackIsZero = TRUE;
        $this->_colorSpace = 'DeviceGray';
      break;
      case self::TIFF_PHOTOMETRIC_INTERPRETATION_YCBCR:
      case self::TIFF_PHOTOMETRIC_INTERPRETATION_RGB:
        $this->_colorSpace = 'DeviceRGB';
      break;
      case self::TIFF_PHOTOMETRIC_INTERPRETATION_RGB_INDEXED:
        $this->_colorSpace = 'Indexed';
      break;
      case self::TIFF_PHOTOMETRIC_INTERPRETATION_CMYK:
        $this->_colorSpace = 'DeviceCMYK';
      break;
      case self::TIFF_PHOTOMETRIC_INTERPRETATION_CIELAB:
        $this->_colorSpace = 'Lab';
      break;
      }
    }
  }

  /**
   * @param $handle
   * @throws Exception
   */
  public function readImageDataBytes($handle): void {
    if(!$this->_imageDataOffset || !$this->_imageDataLength) {
      throw new \Exception('TIFF: imageDataOffset или imageDataLength еще не вычислены');
    }

    $this->_imageDataBytes = '';
    if (is_array($this->_imageDataOffset)) {
      if (!is_array($this->_imageDataLength)) {
        throw new \Exception('TIFF: The image contained multiple data offsets but not multiple data lengths. Tiff may be corrupt.');
      }
      foreach ($this->_imageDataOffset as $idx => $offset) {
        fseek($handle, $this->_imageDataOffset[$idx], SEEK_SET);
        $this->_imageDataBytes .= fread($handle, $this->_imageDataLength[$idx]);
      }
    } else {
      fseek($handle, $this->_imageDataOffset, SEEK_SET);
      $this->_imageDataBytes = fread($handle, $this->_imageDataLength);
    }
    if ($this->_imageDataBytes === '') {
      throw new \Exception('TIFF: No data. Image Corruption');
    }
  }
}
