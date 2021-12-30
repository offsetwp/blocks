# Blocks - Create Gutenberg blocks more easily

## Installation

```bash
composer require offset/blocks
```

## Use

```php
use Offset\Block;

// Create an empty block
$block = new Block();

// Pass the "block.json" file of your Gutenberg block
$block->setSettingsFromJSONPath(__DIR__ . '/block.json');

// Add the file that will be used for the dynamic rendering of your block. You have access to `$attributes` and `$content`.
$block->setRender(__DIR__ . '/template/view.php');

// Save your block in Gutenberg and load the styles and scripts
$block->init();
```

You can also render the content with a function

```php
// Add the file that will be used for the dynamic rendering of your block. You have access to `$attributes` and `$content`.
$block->setRender(function($attributes, $content) {
    return '<div>My block</div>';
});
```

### Filters and overrides

Filters are automatically added to your blocks to give you greater flexibility in development and customization.

#### `offset_block_attributes`

Filter all attributes of all Gutenberg blocks.

##### Parameters

- `$attributes` - `array` - Block attributes. Default empty array

##### Example

```php
function attributes_filter($attributes) {
    $attributes['myValue'] = 'Changed';
    return $attributes;
}

add_filter('offset_block_attributes', 'attributes_filter', 10, 1);
```

#### `offset_block_attributes_{block_name}`

Filter the attributes of Gutenberg blocks that have the same name.

The `block_name` corresponds to the `name` parameter in the `block.json` file, replacing special characters with `_` (example: `offset-pack/block-one` -> `offset_pack_block_one`).

##### Parameters

- `$attributes` - `array` - Block attributes. Default empty array

##### Example

```php
function attributes_filter($attributes) {
    $attributes['myValue'] = 'Changed';
    return $attributes;
}

add_filter('offset_block_attributes_offset_pack_block_one', 'attributes_filter', 10, 1);
```

#### `offset_block_content`

Filters all contents of all Gutenberg blocks.

##### Parameters

- `$content` - `string` - Block content. Default empty string.

##### Example

```php
function content_filter($content) {
    $content .= 'Add new line';
    return $content;
}

add_filter('offset_block_content', 'content_filter', 10, 1);
```

#### `offset_block_content_{block_name}`

Filter the content of Gutenberg blocks that have the same name.

The `block_name` corresponds to the `name` parameter in the `block.json` file, replacing special characters with `_` (example: `offset-pack/block-one` -> `offset_pack_block_one`).

##### Parameters

- `$content` - `string` - Block content. Default empty string.

##### Example

```php
function content_filter($content) {
    $content .= 'Add new line';
    return $content;
}

add_filter('offset_block_content_offset_pack_block_one', 'content_filter', 10, 1);
```

#### `offset_block_render_{block_name}`

Filter the html of Gutenberg blocks that have the same name.

The `block_name` corresponds to the `name` parameter in the `block.json` file, replacing special characters with `_` (example: `offset-pack/block-one` -> `offset_pack_block_one`).

##### Parameters

- `$html` - `string` - Block html. Default empty string
- `$attributes` - `array` - Block attributes. Default empty array
- `$content` - `string` - Block content. Default empty string.

##### Example

```php
function render_filter($html, $attributes, $content) {
    $html .= '<div>Add new block</div>';
    return $html;
}

add_filter('offset_block_render_offset_pack_block_one', 'render_filter', 10, 3);
```

#### `offset_block_is_style_enqueue`

Remove styles from all Gutenberg blocks.

#### Example

```php
add_filter('offset_block_is_style_enqueue', '__return_false');
```

#### `offset_block_is_style_enqueue_{block_name}`

Remove styles from Gutenberg blocks with the same name.

The `block_name` corresponds to the `name` parameter in the `block.json` file, replacing special characters with `_` (example: `offset-pack/block-one` -> `offset_pack_block_one`).

#### Example

```php
add_filter('offset_block_is_style_enqueue_offset_pack_block_one', '__return_false');
```
#### `offset_block_is_script_enqueue`

Remove scripts from all Gutenberg blocks.

#### Example

```php
add_filter('offset_block_is_script_enqueue', '__return_false');
```

#### `offset_block_is_script_enqueue_{block_name}`

Remove scripts from Gutenberg blocks with the same name.

The `block_name` corresponds to the `name` parameter in the `block.json` file, replacing special characters with `_` (example: `offset-pack/block-one` -> `offset_pack_block_one`).

#### Example

```php
add_filter('offset_block_is_script_enqueue_offset_pack_block_one', '__return_false');
```