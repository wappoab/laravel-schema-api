<?php

use Wappo\LaravelSchemaApi\Commands\GenerateClientResources;

it('generates all the client files into our tmp folder', function () {

    File::cleanDirectory($this->base);

    filesystem()->deleteDirectory($this->base);
    filesystem()->makeDirectory($this->base . '/frontend/models', 0755, true);
    filesystem()->makeDirectory($this->base . '/frontend/stores/collections', 0755, true);
    filesystem()->makeDirectory($this->base . '/frontend/enums', 0755, true);
    filesystem()->makeDirectory($this->base . '/frontend/components/forms', 0755, true);

    // run your command
    $this->artisan(GenerateClientResources::class)
        ->assertExitCode(0);

    // assert files exist under tests/tmp/frontend/…
    expect(file_exists($this->base . '/frontend/models/Post.ts'))->toBeTrue();
    expect(file_exists($this->base . '/frontend/stores/collections/posts.ts'))->toBeTrue();
    expect(file_exists($this->base . '/frontend/enums/CollectionName.ts'))->toBeTrue();
    expect(file_exists($this->base . '/frontend/components/forms/PostForm.vue'))->toBeTrue();
});