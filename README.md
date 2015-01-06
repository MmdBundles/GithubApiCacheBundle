# Github API Cache

"Unlimited" API Requests.

Instead of

```
https://api.github.com/repos/twbs/bootstrap/releases
```

use

```
https://your-domain.com/repos/twbs/bootstrap/releases
```

*At the moment are supported only simple GET requests.*

## Install

1. Install bundle with composer

    ```sh
    $ php composer.phar require "mmd/github-api-cache":"dev-master" "sensio/buzz-bundle":"dev-master" "predis/predis":"dev-master"
    ```

2. Include bundle in `app/AppKernel.php`

    ```php
    $bundles = array(
        ...
        new Mmd\Bundle\GithubApiCacheBundle\MmdGithubApiCacheBundle(),
        new Sensio\Bundle\BuzzBundle\SensioBuzzBundle(),
    );
    ```

3. Include bundle's routing in `app/config/routing.yml`

    ```yml
    mmd_mc_monitor:
        resource: "@MmdGithubApiCacheBundle/Resources/config/routing.yml"
        prefix:   /
    ```

4. Install [Redis](https://github.com/dockerfile/redis).

    *You can use this [dockerfile](https://github.com/dockerfile/redis).*

5. Configure parameters in `app/config/parameters.yml`

    ```yml
    mmd_github_api_cache.token: '189...b51'

    mmd_github_api_cache.redis.scheme: 'tcp'
    mmd_github_api_cache.redis.host: '127.0.0.1'
    mmd_github_api_cache.redis.port: 6379
    mmd_github_api_cache.redis.options: {} # https://github.com/nrk/predis#client-configuration
    ```
