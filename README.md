Fooman Speedster
===================

### Installation Instructions
To install the extension, follow the steps in [The Ultimate Guide to Installing Magento Extensions](http://cdn.fooman.co.nz/media/custom/upload/TheUltimateGuidetoInstallingMagentoExtensions.pdf) and [Fooman Speedster Instructions and Troubleshooting Guide](http://cdn.fooman.co.nz/media/custom/upload/InstructionsandTroubleshooting-FoomanSpeedster.pdf).

### Installation Options

**via composer**  
Fooman extension are included in the packages.firegento.com repository so you can install them easily via adding the extension to the require section and then running `composer install` or `composer update`

    "require":{
      "fooman/speedster":"*"
    },

Please note that packages.firegento.com is not always up-to-date - in this case please add the following in the repositories section

    "repositories":[
      {
        "type":"composer",
        "url":"http://packages.fooman.co.nz"
      }
    ],

**via modman**  
`modman clone https://github.com/fooman/common.git`   
`modman clone https://github.com/fooman/speedster.git`   

**via file transfer (zip download)**  
    please see the releases tab for https://github.com/fooman/speedster/releases
    and https://github.com/fooman/common/releases
    
### Known conflicts with Fooman Speedster###
* CANONICAL URLs by Yoast - a workaround is provided in the instructions
* MXPERTS JQUERY BASE - a workaround is provided in the instructions
* Magento's default "Merge JavaScript Files" and "Merge CSS Files" options under System > Configuration > Developer. Do not enable these settings when using Fooman Speedster
