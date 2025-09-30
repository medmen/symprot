rm -rf migrations/*
symfony console doctrine:migrations:diff --from-empty-schema
symfony console doctrine:migrations:rollup
