<?php 
/**
 * Build FFmpeg for iOS, supports armv7, armv7s and i386 (iOS Simulator) architectures.
 *
 * Elf Sundae, www.0x123.com
 * Aug 15, 2013
 */

$ffmpeg_version = '2.0.1';
$ios_sdk_version = '6.1';

//
// For FFmpeg `./configure` params, you can only fill `disable-encoder=NAME`, 
//      `enable-encoder=NAME`, `disable-ExtLibName`, `enable-ExtLibName`, etc.
// 
// See `./configure -h` for all component options and external libraries support.
//
// Now supports external libraries: x264
//
$ffmpeg_configure_options = array(
      'enable-libx264',  
);

$root_dir = dirname(__FILE__);
$ffmpeg_dir = 'ffmpeg-' . $ffmpeg_version;
$ffmpeg_dir_full = "{$root_dir}/{$ffmpeg_dir}";
$build_dir_full = $root_dir . '/build';
$external_lib_dir_full = $root_dir . '/libs';

define('__ARCH_ARMv7__', 'armv7');
define('__ARCH_ARMv7s__', 'armv7s');
define('__ARCH_i386__', 'i386');

global $ffmpeg_version, $root_dir, $ffmpeg_dir, $ffmpeg_dir_full,
$ios_sdk_version, $ffmpeg_configure_options, $build_dir_full, $external_lib_dir_full;
        
///////////////////////////////////////////////////////////////////////////////
//      Main
///////////////////////////////////////////////////////////////////////////////
$now_date_begin = time();
if (file_exists($build_dir_full)) {
        exec("rm -rf $build_dir_full");
}
exec("mkdir $build_dir_full");
if (!file_exists($external_lib_dir_full)) {
        exec("mkdir $external_lib_dir_full");
}

install_gas_preprocessor();

download_unpack_ffmpeg();
if (is_ffmpeg_configure_exists('enable-libx264')) {
        build_lib_x264();        
}
build_ffmpeg();

///////////////////////////////////////////////////////////////////////////////
//      Building external libraries
///////////////////////////////////////////////////////////////////////////////
function build_lib_x264() {
        build_lib_x264_with(__ARCH_i386__);
        build_lib_x264_with(__ARCH_ARMv7__);
        build_lib_x264_with(__ARCH_ARMv7s__);
        
        global $build_dir_full, $external_lib_dir_full;
        
        exec("rm -rf '{$build_dir_full}/libx264'");
        exec("mkdir '{$build_dir_full}/libx264'");
        
        $universal_cmd = "xcrun -sdk iphoneos lipo -output '{$build_dir_full}/libx264/libx264.a' ";
        $universal_cmd .= "-create ";
        $universal_cmd .= "-arch " . __ARCH_i386__ . " '{$external_lib_dir_full}/libx264/build/". __ARCH_i386__ . "/lib/libx264.a' ";
        $universal_cmd .= "-arch " . __ARCH_ARMv7__ . " '{$external_lib_dir_full}/libx264/build/". __ARCH_ARMv7__ . "/lib/libx264.a' ";
        $universal_cmd .= "-arch " . __ARCH_ARMv7s__ . " '{$external_lib_dir_full}/libx264/build/". __ARCH_ARMv7s__ . "/lib/libx264.a' ";
        exec_echo($universal_cmd);
        
        $copy_headers = "cp -rf {$external_lib_dir_full}/libx264/build/". __ARCH_ARMv7s__ ."/include/*.h {$build_dir_full}/libx264";
        exec($copy_headers);
}        

function build_lib_x264_with($arch) {
        global $external_lib_dir_full, $build_dir_full;
        
        $libx264_dir = 'libx264';
        $libx264_dir_full = $external_lib_dir_full . '/' . $libx264_dir;
        echo "[ Building libx264 $arch ... ]\n";
        chdir($external_lib_dir_full);
        if (!file_exists($libx264_dir_full)) {
                $clone_cmd = "git clone git://git.videolan.org/x264.git $libx264_dir";
                exec_echo($clone_cmd);
        } else {
                chdir($libx264_dir_full);
                exec_echo('git pull');
        }
        // build
        chdir($libx264_dir_full);
        exec("make clean");
        exec("rm -rf build/{$arch}");
                
        $host = 'arm-apple-darwin';
        if ($arch == __ARCH_i386__) {
                $host = 'i386-apple-darwin';
        }
        $cmd = 'CC=' . xcode_developer_gcc($arch) . ' ';
        $cmd .= "./configure --host={$host} --prefix=build/{$arch} --extra-cflags='-arch {$arch}' ";
        $cmd .= "--sysroot=" . xcode_developer_SDK_root($arch) . ' ';
        $cmd .= "--extra-ldflags='-L" . xcode_developer_SDK_lib($arch) . " -arch {$arch}' ";
        $cmd .= "--enable-pic --disable-shared --enable-static ";
        if ($arch == __ARCH_i386__) {
                $cmd .= "--disable-asm ";
        }

        $cmd = escapeshellcmd($cmd);
        exec_echo($cmd);
        exec("make && make install");
        
        echo "Done.\n";
}

///////////////////////////////////////////////////////////////////////////////
//      FFmpeg
///////////////////////////////////////////////////////////////////////////////
function download_unpack_ffmpeg() {
        global $root_dir, $ffmpeg_version, $ffmpeg_dir, $ffmpeg_dir_full;
        $ffmpge_package_file = $ffmpeg_dir_full . '.tar.bz2';
        
        chdir($root_dir);
        echo "[ Downloading FFmpeg v$ffmpeg_version... ]\n";
        if (!file_exists($ffmpeg_dir_full)) {
                if ( !file_exists($ffmpge_package_file) ) {
                        $cmd = escapeshellcmd("curl http://ffmpeg.org/releases/{$ffmpeg_dir}.tar.bz2 -o {$ffmpge_package_file}");
                        exec($cmd);
                }  
                echo "[ Unpack $ffmpeg_dir... ]\n";
                exec("tar jxf {$ffmpge_package_file}");
        }
        echo "Done.\n";
}

function build_ffmpeg() {
        build_ffmpeg_with(__ARCH_i386__);
        build_ffmpeg_with(__ARCH_ARMv7__);
        build_ffmpeg_with(__ARCH_ARMv7s__);
        
        global $ffmpeg_dir, $root_dir, $build_dir_full, $ffmpeg_dir_full;
        exec("rm -rf '{$build_dir_full}/{$ffmpeg_dir}'");
        exec("mkdir '{$build_dir_full}/{$ffmpeg_dir}'");
        
        chdir("{$ffmpeg_dir_full}/build");
        $all_lib_files = array();
        $scan_lib_dir = scandir('./armv7/lib');
        foreach ($scan_lib_dir as $f) {
                if (pathinfo($f, PATHINFO_EXTENSION) == 'a') {
                        $all_lib_files[] = $f;
                }
        }
        
        $lib_output_dir = "{$build_dir_full}/{$ffmpeg_dir}/lib";
        if (!file_exists($lib_output_dir)) {
                exec("mkdir '{$lib_output_dir}'");
        }
        foreach ($all_lib_files as $lib) {
                $lib_name = pathinfo($lib, PATHINFO_FILENAME);
                $header_output_dir = "{$build_dir_full}/{$ffmpeg_dir}/{$lib_name}";
                
                exec("mkdir '{$header_output_dir}'");
                $cmd = "xcrun -sdk iphoneos lipo -output '{$lib_output_dir}/{$lib_name}.a' ";
                $cmd .= "-create ";
                $cmd .= "-arch " . __ARCH_i386__ . " '" . __ARCH_i386__ . "/lib/{$lib_name}.a' ";
                $cmd .= "-arch " . __ARCH_ARMv7__ . " '" . __ARCH_ARMv7__ . "/lib/{$lib_name}.a' ";       
                $cmd .= "-arch " . __ARCH_ARMv7s__ . " '" . __ARCH_ARMv7s__ . "/lib/{$lib_name}.a' ";
                exec_echo($cmd);
                
                $copy_haeders = "cp -rf ". __ARCH_ARMv7s__ ."/include/{$lib_name}/*.h {$header_output_dir}";
                exec($copy_haeders);
        }
        
}

function build_ffmpeg_with($arch) {
        global $root_dir, $ffmpeg_dir, $ffmpeg_dir_full, $build_dir_full,
                $ffmpeg_configure_options, $external_lib_dir_full;
                
        echo "[ Building FFmpeg $arch ... ]\n";
        chdir($ffmpeg_dir_full);
        exec("rm -rf build/{$arch}");
        exec("make clean");
        
        
        $cmd = "./configure --cc='". xcode_developer_gcc($arch) ."' ";
        $cmd .= "--prefix=build/{$arch} ";
        $cmd .= "--as='gas-preprocessor.pl " . xcode_developer_gcc($arch) . "' ";
        $cmd .= "--sysroot='" . xcode_developer_SDK_root($arch) . "' ";
        $cmd .= "--extra-ldflags=-L'" . xcode_developer_SDK_lib($arch) . "' ";
        $cmd .= "--enable-pic --enable-cross-compile --enable-gpl --enable-nonfree ";
        $cmd .= "--disable-programs --disable-doc --disable-debug ";
        if (is_ffmpeg_configure_exists('enable-libx264')) {
                $cmd .= "--extra-ldflags=-L'{$external_lib_dir_full}/libx264/build/{$arch}/lib -arch {$arch}' ";
                $cmd .= "--extra-cflags=-I'{$external_lib_dir_full}/libx264/build/{$arch}/include -arch {$arch}' ";
                $cmd .= "--enable-libx264 ";
        }
        $cmd .= "--target-os=darwin ";
        if ($arch == __ARCH_i386__) {
                $cmd .= "--arch=i386 ";
                $cmd .= "--cpu=i386 ";
                $cmd .= "--disable-asm ";
        } else {
                $cmd .= "--arch=arm ";
                if ($arch == __ARCH_ARMv7s__) {
                      $cmd .= "--cpu=cortex-a9 ";  
                } else {
                       $cmd .= "--cpu=cortex-a8 ";
                }
        }
        
        exec_echo($cmd);
        exec("make && make install");
        echo "Done.\n";
}

///////////////////////////////////////////////////////////////////////////////
//      File & Path & Functions
///////////////////////////////////////////////////////////////////////////////
function xcode_developer_dir($arch) {
        $dir = '/Applications/Xcode.app/Contents/Developer/Platforms/';
        if ($arch == __ARCH_i386__) {
                $dir .= 'iPhoneSimulator.platform';
        } else {
                $dir .= 'iPhoneOS.platform';
        }
        $dir .= '/Developer';
        return $dir;
}
function xcode_developer_gcc($arch) {
        return xcode_developer_dir($arch) . '/usr/bin/gcc';
}
function xcode_developer_SDK_root($arch) {
        global $ios_sdk_version;
        $dir = xcode_developer_dir($arch) . '/SDKs/';
        if ($arch == __ARCH_i386__) {
                $dir .= "iPhoneSimulator{$ios_sdk_version}.sdk";
        } else {
                $dir .= "iPhoneOS{$ios_sdk_version}.sdk";
        }
        return $dir;
}
function xcode_developer_SDK_lib($arch) {
        return xcode_developer_SDK_root($arch) . '/usr/lib/system';
}

function exec_echo($cmd) {
        echo $cmd . PHP_EOL;
        exec($cmd);
}

function install_gas_preprocessor() {
        global $root_dir;
        echo("[ Install  gas-preprocessor.pl ]\n");
        exec_echo("sudo cp -f {$root_dir}/gas-preprocessor.pl /usr/local/bin");
        exec_echo("chmod +x /usr/local/bin/gas-preprocessor.pl");
}

function is_ffmpeg_configure_exists($config) {
        global $ffmpeg_configure_options;
        return in_array($config, $ffmpeg_configure_options);
}

$now_date_end = time();
$script_time_interval = ($now_date_end - $now_date_begin) / 60.0;
$script_time_interval = sprintf("%.2f mins", $script_time_interval);
echo "\n*** FFmpeg-iOS-build All Done. $script_time_interval***\n";
echo PHP_EOL;