### Custom wp.media crop workflow

This is a sample WordPress plugin to demonstrate a custom cropping workflow, leveraging the native wp.media 
modal / MediaFrame.
The plugin adds a new meta box to posts.
No data is saved.
!New attachment entries are created for each new cropped image!

####User story:  
1. User is asked to upload a new image to the library or select one from the media library
2. Image crop context opens with a preview of the selected image.
3. User adjusts the selection helper
4. User clicks on 'crop'

Only a predefined, fixed target size is supported yet. 
The provided settings define the min-width & min-height parameters of the imgareaselect plugin as well.

####Usage
    var CropFrame = new media.view.KBCropperFrame({
        cropOptions: {
            maxWidth: 500, //target width
            maxHeight: 360 // target height
        },
        croppedCallback: croppedCallback //defaults to jquery.noop()
    });
    CropFrame.open();

#####callback

    /*
    * @param attachment wp.media.model.Attachmen
    */
    function croppedCallback(attachment){}

####Issues
- if the specified target size exceeds the size of the selected image, the image selection tool is not able to handle it.

####Todo
- check if the size of the selected image qualifies for cropping
-- and/or allow upscaling
- option to open the workflow directly in crop mode by providing an attachment model