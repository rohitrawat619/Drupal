# Transform API

Transform API is meant to create a familiar path for Drupal developers to take
their knowledge of entities, view modes and templates and instead use this to
produce JSON instead of HTML.

# General usage
When accessing any route in Drupal, if the url includes format=json as a GET
parameter, it will rerouted through the Transform API and instead of outputting
HTML, it outputs JSON. This works for all entities and all controller routes as
long as the appropriate plugin or configuration is present. It will also retain
cachability and permission requirements.

User authentification is done through basic auth or any oath2 module like the
[JWT](https://www.drupal.org/project/jwt) module.

# Vocabulary
### Transform
The equivalent theme templates. Takes a component and transforms that something
into transform arrays.

### Transformer
The equivalent of the core renderer service, but instead able to take transforms
and transform arrays and turn them into JSON.

### Transform array
The equivalent of render arrays but for producing JSON instead of HTML.

### Transform mode
The equivalent of view modes, but instead used to turning entities into JSON.

### Transform block
The equivalent of blocks, these are used to supplement he main output of a
route’s JSON response with additional global transforms that are segmented
into regions that can be queried independantly.

# Transforms
The basic component of Transform API is transforms. These are objects that
implement TransformInterface either directly or through extending TransformBase
or PluginTransformBase.

The job of a transform is to represent a component and then transform that
into a transform array.

For instance, any url requested to be transformed through the format=json
parameter is turned into a RequestPathTransform, which will add
BlockTransform(s) and then either create an EntityTransform if the url
points to an entity or a RouteTransform otherwise as the main content.

The module itself includes the following transform types:

### BlockTransform
**Plugins:** Transform/Block

These take a transform block and transforms them in the region they are
configured for. For more information see the transform block section later.

### EntityTransform
**Static creation functions:**  createFromEntity(), createFromMultipleEntities()

**Multiple:** EntityTransform(s) can be given either a single entity id or
an array which will transform multiple entities.

These are used to take a fieldable entity and transform it. It makes use of
transform modes (explained later) to get a configuration of FieldTransform(s)
that it nests inside it.

### FieldTransform
**Plugins:** Transform/Field

**Static creation functions:** createFromEntity()

These are used to transform the individual fields on an entity based on the
configuration from a transform mode. The plugins for this transform takes
certain field types and transforms them in a certain way depending on their
configuration.

### PagerTransform
This takes a pager as it currently exists in Drupal and transforms it.

### RequestPathTransform
This takes a Url, finds transform blocks and finds either a RouteTransform
or EntityTransform based on the Url transformed.

### RouteTransform
**Plugins:** Transform/Route

In case a url is not an entity that can be handled using an EntityTransform
then this transform will take the route name that normally handles an url
and finds a plugin that can transform that route.

### SimpleTransform
This transform just takes a normal array and delivers it as a transform array.

# Transform arrays
Transform arrays are typical PHP arrays both associative and numerical
indexed arrays. Associative arrays will be turned into JSON objects and
numerical indexed arrays will be turned into JSON arrays.

For example:

    $transformation = [
      "type" => "example",
      "links" => [
        [
          "url": "",
          "title": "Kino.dk"
        ]
      ]
    ]

turns into this JSON

    {
      "type": "example",
      "links": [
        {
          "url": "",
          "title": "Kino.dk"
        }
      ]
    }

## Controls
The next thing to know is that any associative key that starts with “#“ are
used to control aspects of the transformation or used to provide information
about the transformation to any alter hooks or post processors.

Here are some globally available transformation controls that you can supply
in a transformation array

### #access
This decides whether the user has access to view this transform. Defaults to
TRUE. If FALSE will return an empty array.

### #access_callback
Provides a callback to determine whether the user has access to view this
transform.

### #cache
This controls the caching of the transformation. This follows the standard
CacheableMetadata from Drupal core.

### #collapse
This is a boolean that tells the transformer that if this array only has one
member, it can be collapsed into a single value. This is particularly useful
if the transformation is just a value that needs to be returned.

### #lazy_transformer
This allows you to add a callback that will be called after caching but before
the response has been sent. This is equivalent to lazy builders in render
arrays. This can be useful if your transformation includes some very dynamic
content that cannot be cached.

### #pre_transform
This provides a callback that alters the transform before transformation
takes place.<br>
<br>
<br>
Any key that starts with “#“ will not be in the final JSON response, but
can be accessed in alter hooks and lazy transformers.

## Nested transforms
It is also possible for transform arrays to nest other transforms within
it. To do so, simply include it in the array as such:

    $transformation = [
      "type" => "example",
      "media" => new EntityTransform('media', $media_id, 'teaser')
    ]

This will tell the transformer to transform the nested transform inside
it and include it in it’s output.

However, it should be noted that while you can directly add the
transformation of a transform, like this:

    $media_transform = new EntityTransform('media', $media_id, 'teaser')
    $transformation = [
      "type" => "example",
      "media" => $media_transform->transform()
    ]

this is bad practice as instead of nested transforms this turns into
one big transform and this does not effectively utilize caching and
re-usability.

## Alter hooks
All transform arrays from transforms can be altered by alter hooks
before they are final.

    hook_transform_alter()

This hook will be called for all transforms that the transformer processes.

    hook_HOOK_transform_alter()

Is a more targeted alter hook for a certain type of transformations. The
transforms themselves supply what identifies each transform allowing for
instance entity transforms to target the specific entity type.

# Transform modes
Drupal core includes two display mode types by default: view modes and
form modes. Transform API adds a third type called transform mode which
is generally equivalent to view modes but made to configure how entities
are transformed into JSON instead of HTML.

New transform modes can be created in the Drupal backend under
Structure → Display modes → Transform modes or through the path
/admin/structure/display-modes/transform

Afterwards these can be utilized for their entity types next to where you
normally configure view and form modes in the field UI. For example for a
the Simple Page content type you would go to
/admin/structure/types/manage/page/transform

Here you can (per transform mode) configure which FieldTransform plugin will
be used to transform a field on the entity or hide it if you do not want it
transformed.

After configuring your fields and transform modes, just click save and you
can export the configuration, ready to be imported on production.

# Transform blocks
In case you want something globally available on all transformed urls then
transform block is a good way to go.

The regions and transform blocks can be configured in a tab in the core
block layout administration interface and works much the same, except the
regions are from config and not your theme.

The blocks are divided into regions that can be queries independently of
eachother on an url and a block can be added to multiple regions with multiple
configurations. For instance a menu block could be added to the header region
with the main menu and another menu block could be added to the footer region
with footer links.

## Alter hook
The output of a transform block can be altered through it’s transform array
alter hook, however the configration of transform blocks can be altered through:

    hook_blocks_transform_config_alter()

This allows you to alter or add any transform blocks you need to.
