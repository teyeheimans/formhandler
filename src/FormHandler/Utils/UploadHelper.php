<?php
namespace FormHandler\Utils;

use FormHandler\Field\UploadField;

class UploadHelper
{
    const MODE_RENAME = 1;
    const MODE_OVERWRITE = 2;
    const MODE_EXCEPTION = 3;

    /**
     * Move the uploaded file of the given field to the given destination.
     *
     * If the destination ends with a slash (either "/" or "\"), the original name will be kept.
     * Example:
     * ```php
     * // here the original name will be kept
     * $newfile = UploadHelper::moveUploadedFile( $field, '/var/www/vhosts/mysite.com/uploads/' );
     * ```
     *
     * Another example:
     * ```php
     * // here, the file will be saved with the name "image.jpg"
     * $newfile = UploadHelper::moveUploadedFile( $field, '/var/www/vhosts/mysite.com/uploads/image.jpg' );
     * ```
     *
     * As third parameter, you can influence what happens if the file trying to write exists.
     * You can use the MODE_* constants for this.
     * Example:
     * ```php
     * // here the original name will be kept
     * // if it exists, it will be renamed
     * $newfile = UploadHelper::moveUploadedFile(
     *     $field,
     *     '/var/www/vhosts/mysite.com/uploads/',
     *     UploadHelper::MODE_RENAME
     *  );
     * ```
     *
     * As final parameter you can decide if the upload path should be created it it does not exists.
     * This is default disabled (false). Set to true to enable this functionality.
     * If you want to give the created directory a specific chmod, then enter this instead of true.
     * Example:
     * ```php
     * $newfile = UploadHelper::moveUploadedFile(
     *     $field,                                    // the field
     *     '/var/www/vhosts/mysite.com/uploads/',     // directory where to save the file
     *     UploadHelper::MODE_RENAME,                 // what to do if exists
     *     0644                                       // create destination dir if not exists with this mode
     * );
     * ```
     *
     * NOTE: Uploading multiple files using 1 uploadfield is supported, but obly if the given destination is a folder.
     * If using multiple file uploads, and $existMode is using MODE_EXCEPTION, then it could be that
     * the first two files are uploaded and the third one us causing an exception. This method will not "clean up" the
     * first two moved files!
     *
     * @param UploadField $field The field where the file was uploaded in
     * @param string $destination The destination where to save the file
     * @param int $existMode Mode what to do if the file exists. Default: rename
     * @param boolean $createDestinationIfNotExist Create the $destination path if not exists or not.
     *                                              You can also give a umask here (like 644).
     * @return string                               The destination of the new file or null on an error.
     *                                              When multiple files are uploaded, this will be an array
     *
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    public static function moveUploadedFile(
        UploadField &$field,
        $destination,
        $existMode = self::MODE_RENAME,
        $createDestinationIfNotExist = false
    ) {
        $filedata = $field->getValue();

        // is multiple file uploads enabled?
        if ($field->getMultiple()) {
            // not ending with a slash?
            $lastChar = substr($destination, -1);
            if (!($lastChar == '/' || $lastChar == '\\')) {
                throw new \Exception(
                    'You have given a destination filename. This uploadfield allows ' .
                    'multiple files to be uploaded. We can\'t handle that!'
                );
            }
        }

        // to walk "something", make an array of the name, even if we are not using multiple file uploads.
        if (!is_array($filedata['name'])) {
            $filedata['name'] = array($filedata['name']);
        }

        $originalDestination = $destination;

        $result = array();

        // walk all uploaded files
        foreach ($filedata['name'] as $index => $filename) {
            // keep the original filename if wanted
            $lastChar = substr($originalDestination, -1);
            if ($lastChar == '/' || $lastChar == '\\') {
                $destination = $originalDestination . $filename;
            } else {
                $destination = $originalDestination;
            }

            // if the file exists...
            if (file_exists($destination)) {
                // throw exception wanted ?
                if ($existMode == UploadHelper::MODE_EXCEPTION) {
                    throw new \Exception(sprintf(
                        'Could not upload the file "%s" to destination "%s" because ' .
                        'a file with this name already exists in this folder!',
                        $filename,
                        $destination
                    ));
                } // should we rename the file...
                else {
                    if ($existMode == UploadHelper::MODE_RENAME) {
                        $destination = UploadHelper::getNonExistingFilename($destination);
                    } // a different unkown mode is given, throw exception
                    else {
                        if ($existMode != UploadHelper::MODE_OVERWRITE) {
                            throw new \UnexpectedValueException(
                                'Incorrect "exists" mode given! You have to use one of the ' .
                                'MODE constants of the UploadHelper as mode!'
                            );
                        }
                    }
                }
            }

            $dirname = dirname($destination);
            // should we create the destination path if not exists?
            if ($createDestinationIfNotExist) {
                if (!is_dir($dirname) &&
                    !mkdir(
                        $dirname,
                        is_bool($createDestinationIfNotExist) ? 0777 : $createDestinationIfNotExist,
                        true
                    )
                ) {
                    throw new \Exception(sprintf(
                        'Failed to create the destination directory "%s"',
                        $dirname
                    ));
                }
            }

            if (!is_writable($dirname)) {
                throw new \Exception(
                    sprintf(
                        'Failed to move uploaded file because the destination ' .
                        'directory is not writable! Directory: "%s"',
                        $dirname
                    )
                );
            }

            if (is_array($filedata['tmp_name'])) {
                // move the file
                if (move_uploaded_file($filedata['tmp_name'][$index], $destination)) {
                    $result[$index] = $destination;
                }
            } // not an array (e.g. not multiple file uploads)
            else {
                // move the file
                if (move_uploaded_file($filedata['tmp_name'], $destination)) {
                    return $destination;
                }
            }
        }

        return $result;
    }

    /**
     * This function takes a path to a file. If the file exists,
     * the filename will be altered and a digit will be added to make the
     * filename unique (non-existing).
     *
     *  So, if /tmp/image.jpg exists, it becomes /tmp/image(1).jpg.
     *
     * @param string $destination
     * @return string
     */
    public static function getNonExistingFilename($destination)
    {
        // find a unique name
        $dir = dirname($destination);
        $file = basename($destination);
        $ext = UploadHelper::getFileExtension($file);
        if ($ext) {
            $ext = '.' . $ext;
            $file = substr($file, 0, 0 - strlen($ext));
        }

        $extra = "";
        $i = 1;

        while (file_exists($dir . DIRECTORY_SEPARATOR . $file . $extra . $ext)) {
            $extra = '(' . $i++ . ')';
        }

        return $dir . DIRECTORY_SEPARATOR . $file . $extra . $ext;
    }

    /**
     * Return the extension of a filename, or null if no extension could be found.
     * The extension is lower-cased and does NOT contain a leading dot.
     *
     * @param string $filename
     * @return string
     */
    public static function getFileExtension($filename)
    {
        $filename = basename($filename);

        // remove possible query string
        $pos = strpos($filename, '?');
        if ($pos !== false) {
            $filename = substr($filename, 0, $pos);
        }

        // retrieve the extension
        $pos = strrpos($filename, '.');
        if ($pos !== false) {
            return strtolower(substr($filename, $pos + 1));
        }

        return null;
    }

    /**
     * Merge two images together.
     *
     * This function merges an image on top of the given $original image. This is usually done to add a
     * stamp, logo or watermark to the image.
     *
     * $original is the source where the $stamp should be added on top. Only gif, jpg and png files are supported.
     * Be aware: The original file will be changed!
     *
     * $stamp is the image which will be added on top of the original. Only gif, jpg and png files are supported.
     *
     * With the $align and the $verticalAlign params you can set the position where the $stamp image should be placed
     * in the original image. You can set the position in pixels, percents or using a keyword like:
     * - bottom
     * - top
     * - center
     * - left
     * - right
     * - middle
     *
     * If you want a specific color transparant, then you should set the color code
     * in the $transparant param. This can either be an array with r, g and b, values,
     * or a hexadecimal value like #ff99ee.
     * Please note that transparancy only works for PNG24 files.
     * {@see http://www.formhandler.net/topic/2848/MergeImage_with_transparencey.html#msg3053)
     *
     * Example code:
     * <code>
     * UploadHelper::mergeImage(
     *       '/var/www/vhosts/mysite.com/images/myimage.jpg',
     *     '/var/www/vhosts/mysite.com/mystamp.png',
     *     'right',    # you can also use precentages (like "90%") or pixels (like "20" or "20px")
     *     'bottom',    # idem
     *     '#ff0000'    # replace the red color for transparant
     * );
     * </code>
     *
     * @param string $original
     * @param string $stamp
     * @param string|int $align
     * @param string|int $verticalAlign
     * @param array|string $transparant
     * @throws \Exception
     * @return void
     */
    public static function mergeImage($original, $stamp, $align, $verticalAlign, $transparant = null)
    {
        // check if the source exists
        if (!is_file($original) || !($orgSize = getimagesize($original))) {
            throw new \Exception(sprintf(
                'Could not find or read the original image for merging: %s',
                $original
            ));
        }

        // check if the stamp exists
        if (!is_file($stamp) || !($stampSize = getimagesize($stamp))) {
            throw new \Exception(sprintf(
                'Could not find or read the stamp image for merging: %s',
                $stamp
            ));
        }

        if (!function_exists('imagecopyresampled')) {
            throw new \Exception(
                'The required function "imagecopyresampled" does not exists!'
            );
        }

        // make an rgb color of the given color
        if ($transparant) {
            if (!is_array($transparant)) {
                if (substr($transparant, 0, 1) == '#') {
                    $transparant = substr($transparant, 1);
                }

                if (strlen($transparant) == 6) {
                    $transparant = array(
                        hexdec($transparant[0] . $transparant[1]),
                        hexdec($transparant[2] . $transparant[3]),
                        hexdec($transparant[4] . $transparant[5])
                    );
                } elseif (strlen($transparant) == 3) {
                    $transparant = array(
                        hexdec($transparant[0] . $transparant[0]),
                        hexdec($transparant[1] . $transparant[1]),
                        hexdec($transparant[2] . $transparant[2])
                    );
                }
            }
        }

        $ext = UploadHelper::getFileExtension($original);

        // Open the current file (get the resource )
        $imageSource = UploadHelper::openImage($original, $ext);

        // create the "new" file recourse with the size of the original image
        $merged = imagecreatetruecolor($orgSize[0], $orgSize[1]);

        // Open the stamp image
        $stampSource = UploadHelper::openImage($stamp);

        // Transparant color...
        if (is_array($transparant) && sizeof($transparant) >= 3) {
            $color = imagecolorallocate($stampSource, $transparant[0], $transparant[1], $transparant[2]);
            imagecolortransparent($stampSource, $color);
        }

        // Copy the current file to the new one
        imagecopy($merged, $imageSource, 0, 0, 0, 0, $orgSize[0], $orgSize[1]);
        imagealphablending($merged, true); //allows us to apply a 24-bit watermark over $image
        imagedestroy($imageSource); // close the original one, not needed anymore

        // retrieve the new position for the stamp
        $posX = UploadHelper::getPosition($orgSize[0], $stampSize[0], $align);
        $posY = UploadHelper::getPosition($orgSize[1], $stampSize[1], $verticalAlign);

        // copy the stamp to the new image
        // we do NOT use imagecopymerge here because transparancy in a PNG file is not copied along.
        //imagecopymerge( $merged, $stampSource, $posX, $posY, 0, 0, $stampSize[0], $stampSize[1], 100 );
        imagecopy($merged, $stampSource, $posX, $posY, 0, 0, $stampSize[0], $stampSize[1]);

        // save the image (overwrite the original file)
        UploadHelper::closeImage($ext, $merged, $original, 100);

        // close the resources
        imagedestroy($stampSource);
        imagedestroy($merged);
    }

    /**
     * Open an image based on it's extension
     * @param string $file
     * @param string $ext
     * @return resource
     * @throws \Exception
     */
    protected static function openImage($file, $ext = null)
    {
        if ($ext == null) {
            $ext = UploadHelper::getFileExtension($file);
        }

        // get the new image instance
        if ($ext == 'jpg' || $ext == 'jpeg') {
            $image = @imagecreatefromjpeg($file);
            if (!$image) {
                throw new \Exception('Failed to open JPG. Maybe the file is not a JPG after all?');
            }
        } elseif ($ext == 'png') {
            if (!self::isPngFile($file)) {
                throw new \Exception('The PNG file seems to be invalid!');
            }

            $image = @imagecreatefrompng($file);
            if (!$image) {
                throw new \Exception('Failed to open PNG. Maybe the file is not a PNG after all?');
            }
        } elseif ($ext == 'gif') {
            if (!function_exists('imagecreatefromgif')) {
                throw new \Exception(
                    'GIF images can not be resized because the function "imagecreatefromgif" is not available.'
                );
            }
            $image = @imagecreatefromgif($file);
            if (!$image) {
                throw new \Exception('Failed to open GIF. Maybe the file is not a GIF after all?');
            }
        } else {
            throw new \Exception(
                'Only images with the following extension are allowed: jpg, jpeg, png, gif'
            );
        }

        return $image;
    }

    /**
     * Check if a file is a PNG file. Does not depend on the file's extension
     *
     * @param string $filename Full file path
     * @return boolean|null
     */
    public static function isPngFile($filename)
    {
        // check if the file exists
        if (!file_exists($filename)) {
            return null;
        }

        // define the array of first 8 png bytes
        $png_header = array(137, 80, 78, 71, 13, 10, 26, 10);
        // or: array(0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A);

        // open file for reading
        $f = fopen($filename, 'r');

        // read first 8 bytes from the file and close the resource
        $header = fread($f, 8);
        fclose($f);

        // convert the string to an array
        $chars = preg_split('//', $header, -1, PREG_SPLIT_NO_EMPTY);

        // convert each charater to its ascii value
        $chars = array_map('ord', $chars);

        // return true if there are no differences or false otherwise
        return (count(array_diff($png_header, $chars)) === 0);
    }

    /**
     * Get the position of the stamp image based on the original size image
     *
     * @param int $size : the size of the image (width of height)
     * @param int $stampSize : the size of the stamp (width of height)
     * @param string $where : position where to put the stamp on the image
     * @return int
     */
    protected static function getPosition($size, $stampSize, $where)
    {
        // percentage ?
        if (strpos($where, '%') !== false) {
            $percent = str_replace('%', '', $where);
            $part = $size / 100;
            $x = ceil($percent * $part);
        } else {
            if (is_numeric(str_replace('px', '', strtolower($where)))) {
                $x = $where;
            } else {
                // get the pos for the copyright stamp
                switch (strtolower($where)) {
                    case 'top':
                    case 'left':
                        $x = 0;
                        break;
                    case 'middle':
                    case 'center':
                        $x = ceil($size / 2) - ceil($stampSize / 2);
                        break;
                    case 'bottom':
                    case 'right':
                        $x = $size - $stampSize;
                        break;
                    default:
                        $x = 0;
                }
            }
        }

        return $x;
    }

    /**
     * Close an image based on its extension
     *
     * @param string $ext The image extension without leading dot.
     * @param resource $image
     * @param string $destination
     * @param int $quality
     * @return bool
     * @throws \Exception
     */
    protected static function closeImage($ext, $image, $destination, $quality = 80)
    {
        if ($ext == 'jpg' || $ext == 'jpeg') {
            return imagejpeg($image, $destination, $quality);
        } elseif ($ext == 'png') {
            return imagepng($image, $destination);
        } elseif ($ext == 'gif' && function_exists('imagegif')) {
            return imagegif($image, $destination);
        } else {
            throw new \Exception(
                'Only images with the following extension are allowed: jpg, jpeg, png, gif'
            );
        }
    }

    /**
     * This is a function to both resize and crop images.
     * Note: this function has only been minimally tested, and recursively calls itself.
     * It should not be used for any serious use-case yet, but is a start.
     *
     * @param string $original
     * @param string $destination
     * @param integer $targetX
     * @param integer $targetY
     * @param integer $quality
     * @return void
     * @throws \Exception
     */
    public static function resizeAndCropImage($original, $destination, $targetX, $targetY, $quality = 80)
    {
        if (!$destination) {
            $destination = $original;
        }

        // check if the source exists
        if (!is_file($original) || !($size = getimagesize($original))) {
            throw new \Exception(sprintf(
                'Could not find or read the original image for cropping: %s',
                $original
            ));
        }

        // get the original size
        list($orgWidth, $orgHeight) = $size;

        $aspectRatio = $orgWidth / $orgHeight;

        if (($orgWidth / $orgHeight) == ($targetX / $targetY)) {
            self::resizeImage($original, $destination, $targetX, $targetY, $quality);
            return;
        }

        $targetXResize = $targetX;
        $targetYResize = $orgHeight / $aspectRatio;

        $fromY = ($targetYResize / 2) - ($targetY / 2);

        self::resizeImage($original, $destination, $targetXResize, $targetYResize, 100);
        self::cropImage($destination, $destination, 0, $fromY, $targetX, $targetY, $quality);
    }

    /**
     * Resize an image by using GD.
     *
     * $source is the path to the source image file. Only jpg, gif and png files are allowed.
     * Gif images are only supported if the function "imagecreatefromgif" exists.
     *
     * $destination is the full path to the image how it should be saved. If the file exists,
     * it will be overwritten. If the destination ends with a slash (both are allowed), then the original
     * file name is kept and saved in the $destination directory.
     * The destination folder needs to exists!
     * If the destination file extension is different from the source,
     * the image will also be converted to the new file type.
     *
     * $newWidth and $newHeight are the new image size in pixels.
     * If both $newWidth and $newHeight are given, these will be used for the new image. If only one of both are given,
     * and $constrainProportions is set to true (default), then the other value will be calculated automatically
     * to constrain the proportions.
     *
     * $quality is the quality of the saved resized image if this is a JPG image.
     * For the other formats, this parameter is ignored.
     *
     * If $constrainProportions is set to false, the original size will be used for the missing size.
     * If both sizes are missing, the original will be used.
     *
     * Example:
     * <code>
     * UploadHelper::resizeImage(
     *     'images/image.jpg',   // the original image
     *     'images/thumbs/',     // save the resized image in this dir, keep the original filename.
     *                           // If exists, it will be overwritten.
     *
     *     250,                  // make the new image 250 pixels width
     *     null,                 // no height given
     *     80,                   // quality to safe the image in (in percentage), default 80
     *     true                  // auto calculate the missing size so that the proportions are kept of the file?
     *                           // (default true)
     * );
     * </code>
     *
     * @param string $source
     * @param string $destination
     * @param int $newWidth
     * @param int $newHeight
     * @param int $quality
     * @param boolean $constrainProportions
     * @return string The location where the image was saved
     * @throws \Exception
     */
    public static function resizeImage(
        $source,
        $destination = null,
        $newWidth = null,
        $newHeight = null,
        $quality = 80,
        $constrainProportions = true
    ) {
        // check if the source exists
        if (!is_file($source) || !($size = getimagesize($source))) {
            throw new \Exception(sprintf('Could not find or read the file to resize: %s'), $source);
        }

        // no destination given? Then overwrite the original one!
        if (!$destination) {
            $destination = $source;
        }

        // get the original size
        list($orgWidth, $orgHeight) = $size;
        // store the requested size
        $myNewWidth = $newWidth ? $newWidth : $orgWidth;
        $myNewHeight = $newHeight ? $newHeight : $orgHeight;

        // should we keep the proportions?
        if ($constrainProportions) {
            // both sizes are given? Then only use the size which is the largest of the original image.
            if (!($newWidth xor $newHeight)) {
                if ($orgWidth > $orgHeight) {
                    $newHeight = null;
                } else {
                    $newWidth = null;
                }
            }

            if ($newWidth) {
                $newHeight = ($newWidth / ($orgWidth / 100)) * ($orgHeight / 100);
                // Check again if the images size is not out of proportion
                if ($newHeight > $myNewHeight) {
                    $newHeight = $myNewHeight;
                    $newWidth = ($newHeight / ($orgHeight / 100)) * ($orgWidth / 100);
                }
            } else {
                $newWidth = ($newHeight / ($orgHeight / 100)) * ($orgWidth / 100);
                // Check again if the images size is not out of proportion
                if ($newWidth > $myNewWidth) {
                    $newWidth = $myNewWidth;
                    $newHeight = ($newWidth / ($orgWidth / 100)) * ($orgHeight / 100);
                }
            }
        } // dont keep proportions
        else {
            if (!$newWidth) {
                $newWidth = $orgWidth;
            }

            if (!$newHeight) {
                $newHeight = $orgHeight;
            }
        }

        // add the original filename
        $lastChar = substr($destination, -1);
        if ($lastChar == '/' || $lastChar == '\\') {
            $destination .= basename($source);
        }

        $gdVersion = UploadHelper::getGDVersion();
        if (!$gdVersion) {
            throw new \Exception('Could not resize image because GD is not installed!');
        }

        $ext = UploadHelper::getFileExtension($source);
        $destExt = UploadHelper::getFileExtension($destination);

        // open the image
        $image = UploadHelper::openImage($source, $ext);

        // generate the new image
        if ($gdVersion >= 2) {
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $orgWidth, $orgHeight);
        } else {
            $resized = imagecreate($newWidth, $newHeight);
            imagecopyresized($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $orgWidth, $orgHeight);
        }

        // close the image
        $result = UploadHelper::closeImage($destExt, $resized, $destination, $quality);

        // clean up
        imagedestroy($image);
        imagedestroy($resized);

        // quality assurance
        if (!$result) {
            throw new \Exception('Error while writing image file after resize process in UploadHelper.');
        }

        return $destination;
    }

    #################################################################
    ############ From here, protected helper methods ################
    #################################################################

    /**
     * Return the GD version installed.
     * Returns 0 when no version installed.
     * @return int
     */
    public static function getGDVersion()
    {
        static $version = null;

        if ($version === null) {
            if (!extension_loaded('gd')) {
                return $version = 0;
            }

            // use the gd_info() function if possible.
            if (function_exists('gd_info')) {
                $info = gd_info();
                if (!empty($info['GD Version']) && preg_match('/\d/', $info['GD Version'], $match)) {
                    return $version = $match[0];
                }
            }

            if (!preg_match('/phpinfo/', ini_get('disable_functions'))) {
                // ...otherwise use phpinfo().
                ob_start();
                phpinfo(8);
                $info = ob_get_contents();
                ob_end_clean();
                $info = stristr($info, 'gd version');
                if ($info && preg_match('/\d/', $info, $match)) {
                    $version = $match[0];
                }
            }

            $version = 1;
        }

        return $version;
    }

    /**
     * Crop an image.
     *
     * Example:
     * <code>
     * UploadHelper::cropImage( 'path/to/file.jpg', '', 10, 10, 600, 600 );
     * </code>
     *
     * @param string $original The file which should be cropped. Supported formats are jpg, gif and png
     * @param string $destination The file where the cropped image should be saved in.
     *                              When an empty string is given, the original file is overwritten
     * @param int $x The x coordinate where we should start cutting
     * @param int $y The x coordinate where we should start cutting
     * @param int $width The width of the cut
     * @param int $height The height of the cut
     * @param int $quality
     * @return string Returns the full path to the destination file, or null if something went wrong.
     * @throws \Exception
     */
    public static function cropImage($original, $destination, $x, $y, $width, $height, $quality = 80)
    {
        if (!$destination) {
            $destination = $original;
        }

        // check if the source exists
        if (!is_file($original) || !($size = getimagesize($original))) {
            throw new \Exception(sprintf(
                'Could not find or read the original image for cropping: %s',
                $original
            ));
        }

        // check if gd is supported
        $gdVersion = UploadHelper::getGDVersion();
        if (!$gdVersion) {
            throw new \Exception('Could not resize image because GD is not installed!');
        }

        $ext = UploadHelper::getFileExtension($original);

        // open the image
        $image = UploadHelper::openImage($original, $ext);

        // generate the new image
        if ($gdVersion >= 2) {
            $cropped = imagecreatetruecolor($width, $height);
            imagecopyresampled($cropped, $image, 0, 0, $x, $y, $width, $height, $width, $height);
        } else {
            $cropped = imagecreate($width, $height);
            imagecopyresized($cropped, $image, 0, 0, $x, $y, $width, $height, $width, $height);
        }

        // close the image
        UploadHelper::closeImage($ext, $cropped, $destination, $quality);

        // clean up
        imagedestroy($image);
        imagedestroy($cropped);

        return $destination;
    }

    /**
     * Return the max upload size in bytes
     * @return int
     */
    public static function getMaxUploadSize()
    {
        if (!ini_get('file_uploads')) {
            return 0;
        }

        $max = 0;

        try {
            $max = self::iniSizeToBytes(ini_get('upload_max_filesize'));
        } catch (\Exception $e) {
        }

        try {
            $max2 = self::iniSizeToBytes(ini_get('post_max_size'));
            if ($max2 < $max) {
                $max = $max2;
            }
        } catch (\Exception $e) {
        }

        return $max;
    }

    /**
     * Make the ini size like 2M to bytes
     *
     * @param string $str
     * @return int
     * @throws \Exception
     */
    public static function iniSizeToBytes($str)
    {
        if (!preg_match('/^(\d+)([bkm]*)$/i', trim($str), $parts)) {
            throw new \Exception("Failed to convert string to bytes!");
        }

        switch (strtolower($parts[2])) {
            case 'm':
                return (int)($parts[1] * 1048576);
            case 'k':
                return (int)($parts[1] * 1024);
            case 'b':
            default:
                return (int)$parts[1];
        }
    }
}