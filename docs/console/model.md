# Basic model

php console make:model User

## Full Eloquent model with migration and factory

php console make:model Post --eloquent --migration --factory

## Pivot model for many-to-many relationships

php console make:model UserRole --pivot

## Model with all features

php console make:model Product -e -m -c -f -s -d --fillable

## Model trait

php theplugs make:model Timestampable --trait

### Multiple Model Types

--eloquent or -e: Full Eloquent ORM model with database features
--pivot or -p: Pivot model for many-to-many relationships
--trait or -t: Model trait for reusable functionality

### Advanced Options

--migration or -m: Generate migration file
--controller or -c: Generate corresponding controller
--factory or -f: Generate model factory
--seeder or -s: Generate database seeder
--soft-deletes or -d: Add soft delete functionality
--uuid or -u: Use UUID primary keys
--fillable: Add fillable attributes array
--guarded: Add guarded attributes array
--no-timestamps: Disable timestamps
