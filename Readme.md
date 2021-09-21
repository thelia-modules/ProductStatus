# Product Status

Add a short description here. You can also add a screenshot if needed.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is ProductStatus.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/product-status-module:~1.0
```

## Usage

You can create status in module/configuration and associate it in each modules tab's product. The status will show a decription and a code on the product page in front-office

## Hook
hook `module.configuration` is used for configuration page

hook `product.tab-content` is used to display module in the modules tab of each product

hook `product.bottom` is used to dislay status content un frontOffice
## Loop

If your module declare one or more loop, describe them here like this :

[ProductProductStatusLoop]

|Variable   |Description |
|---        |--- |
|$LOCALE    | language variable |
|$STATUS_ID    | the status id of the current product |
|$STATUS_COLOR | the status color of th current product|
|$STATUS_DESCRIPTION|the status description of the current product|
|$STATUS_TITLE| the status name of the current product|

[ProductStatusLoop]

|Variable   |Description |
|---        |--- |
|$LOCALE    | language variable |
|$ID    | the id of the status |
|$COLOR | the color of the status|
|$DESCRIPTION|the description status|
|$TITLE| the name of the status|
|$CODE| the code of the status|
|$UPDATED_AT| the last updated time of the status|
|$CREATED_AT| the created time of the status|

### Exemple

    {if $STATUS_ID !== 1 and !empty($STATUS_TITLE)}
        <p id="status-banner" style="border-style: solid; border-color: #dbbf7c;">
            {$STATUS_TITLE|upper}
        </p>
        <div class="mt-3">
            {if $STATUS_DESCRIPTION}
                <i>
                    {$STATUS_TITLE} : {$STATUS_DESCRIPTION}
                </i>
            {/if}
        </div>
    {/if}
