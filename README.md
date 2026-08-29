# dream-ability-support
php utility package

## Keeping hand-written code in generated files

Code generation rewrites the whole file, so hand-written code is lost. Skipping an existing file
(`createFile()` without `$isOverwrite`) avoids that, but then the file is generated only once and never
picks up a change of the template.

`PreservedBlockMerger` regenerates the file and takes over only the ranges that are marked as preserved.
Any number of ranges can be marked in one file, and they are matched by their id, not by their position.

```php
use StepUpDream\DreamAbilitySupport\Supports\CodeGeneration\PreservedBlockMerger;
use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;

$fileOperation->createFilePreservingBlock($generatedContent, $filePath);

// Any keyword can be used, so the marker can follow the comment syntax of the target file.
$fileOperation->createFilePreservingBlock(
    $generatedContent,
    $filePath,
    new PreservedBlockMerger('BEGIN CUSTOM CODE', 'END CUSTOM CODE')
);
```

The template declares the empty blocks:

```php
class Sample
{
    // preserve:begin(trait)
    // preserve:end(trait)

    public function generated(): void
    {
    }

    // preserve:begin(method)
    // preserve:end(method)
}
```

The lines between the markers are taken over from the existing file, and everything else is regenerated.
The marker lines themselves always come from the template, so a marker can be renamed or moved by the template.

Rules of the markers:

- The id is required and must be unique in the file. The block is matched by the id, so moving a block in the
  template moves the hand-written code with it.
- A block cannot be nested, and it must be closed by the same id.
- When the existing file has a block that the template no longer declares, a `LogicException` is thrown so that the
  hand-written code is not dropped without notice. Pass `throwOnMissingBlock: false` to allow it.
- The file is written only when the merged content is different, so a regeneration that changes nothing does not
  touch the file.

Note that a marker based merge is not the only option, and it is not the best one for every file:

- **Split the generated code and the hand-written code into two files** (an abstract base class that is always
  overwritten and a subclass that is generated only once, a trait, a partial config file, and so on). Nothing has to
  be merged, so this is the safest choice whenever the language allows it.
- **Merge with git** (a three-way merge against the previously generated content). It handles a change of the
  template outside of the marked ranges too, but it needs the previous generation to be stored and it can conflict.
- **Merge the syntax tree** (for example with `nikic/php-parser`). It keeps a hand-written method without any marker,
  but it is specific to one language and much harder to keep predictable.

`PreservedBlockMerger` is the middle ground: explicit, language independent, and idempotent.
