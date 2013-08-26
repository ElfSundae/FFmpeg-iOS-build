Build FFmpeg for iOS, supports armv7, armv7s and i386 (iOS Simulator) architectures.

###Requirement
* Xcode 4.2+ with Command Line Tools installed.
* php 5.3+ to execute this script.

###Build
        $ git clone https://github.com/ElfSundae/FFmpeg-iOS-build
        $ cd FFmpeg-iOS-build
        $ git submodule init
        $ git submodule update
        $ php make.php

Take a coffee break, the script will output libraries and header files in `FFmpeg-iOS-build/build`.        

####Test:

  1. Create a new Xcode project, copy `FFmpeg-iOS-build/build` to `<project_root>/ffmpeg`.
  2. Right click the project name in Xcode Navigator, select `Add Files to ...`, locate the `ffmpeg` then click `Add` button.
          Do **NOT** select the "Copy items into destination group’s folder (if needed)" checkbox as the `ffmpeg` direcotry
          has been already in the project directory.  
          Aslo you can drag the `build` directory to the Xcode Navigator Pannel, then **SELECT** the "Copy item into..." checkbox.
  3. Add frameworks from "Build Phases":   
          
        libiconv.2.4.0.dylib
        libz.dylib
        libbz2.1.0.dylib
  4. In the "Build Settings", edit the "Header Search Paths", fill into the header paths, e.g.  
            
          $(SRCROOT)/ffmpeg/libx264  
          $(SRCROOT)/ffmpeg/ffmpeg-2.0.1  
  5. In `AppDelegate.m`, in the `-application:didFinishLaunchingWithOptions:` method, you can code as followings:  
  
          [self.window makeKeyAndVisible];
          av_register_all();
          printf("%s\n", avformat_configuration());
          return YES;      
  6. Build & Run.                        

###Demo
* [ESMediaPlayerDemo](https://github.com/ElfSundae/ESMediaPlayerDemo)         
* [FFmpegAudioTest](https://github.com/ElfSundae/FFmpegAudioTest)

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
              'disable-programs',
              'disable-doc',
              'disable-debug', 
              /* external libraries */
              'enable-libx264',
        );


###TODO
* Support more [ffmpeg external libraries](http://ffmpeg.org/general.html#External-libraries).
* <del>Publish an iOS demo project.</del>  
         <del>See "[Test](#test)" section above.</del>
         
###References
* [gas-preprocessor](https://github.com/yuvi/gas-preprocessor)
* [How to Prepare Your Mac for iOS Development with FFmpeg Libraries](http://www.tangentsoftworks.com/2012/11/12/how-to-prepare-your-mac-for-ios-development-with-ffmpeg-libraries)
* [Using libavformat and libavcodec by Martin Böhme](http://www.inb.uni-luebeck.de/~boehme/using_libavcodec.html), a good overview of the FFmpeg APIs, though quite out dated.
* [FFmpeg Documentation](http://ffmpeg.org/doxygen/trunk/index.html)
* [iFrameExtractor](https://github.com/lajos/iFrameExtractor)
* [objc-FFmpegAudioTest](https://github.com/pontago/objc-FFmpegAudioTest)