Build FFmpeg for iOS, supports armv7, armv7s and i386 (iOS Simulator) architectures.

###Requirement
* Xcode 4.2+ with Command Line Tools installed.
* php 5.3+ to execute this script.

###Build
        $ git clone https://github.com/ElfSundae/FFmpeg-iOS-build
        $ cd FFmpeg-iOS-build
        $ php make.php

Take a coffee break, the script will output libraries and header files in `FFmpeg-iOS-build/build`.

###Customization
Edit `make.php` :

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

###TODO
* Support more [ffmpeg external libraries](http://ffmpeg.org/general.html#External-libraries).
* Publish an iOS demo project.

###Refrence
* [gas-preprocessor](https://github.com/yuvi/gas-preprocessor)
