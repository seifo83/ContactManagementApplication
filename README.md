# Contact Management Application

This project is a technical exercise that involves managing contacts through a Symfony application.

## Exercise Objective

The main goal is to complete the implementation of the `UpdateContactCommand` which allows updating contacts in the database.

## Project Setup

### Prerequisites

-   Docker and Docker Compose
-   PHP 8.2 or higher + PostgreSQL drivers
-   Composer

### Installation

1. Clone the repository

    ```
    git clone [repo_url]
    cd exo7
    ```

2. Install dependencies

    ```
    composer install
    ```

3. Launch Docker environment

    ```
    docker-compose up -d
    ```

4. Create the database

    ```
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

### Task to Complete

Complete the `UpdateContactCommand` located in the `src/Command/` directory.

This command should allow creating/updating information for an existing contact in the database.

You will need to launch the command at least two times, the first one to insert all contacts and the second one to update.

#### Expected features:

-   Contacts

    -   Search for a contact by its identifier
    -   Update its information (first name, last name, email, etc.)
    -   Remove old contacts
    -   Save changes to the database

-   Organizations

    -   Search for an organization by its identifier
    -   Update its information (name, phone number, address, etc.)
    -   Remove old organizations
    -   Save changes to the database

-   Contacts/organizations

    -   Link contacts and organizations (a contact can have multiple organizations)
    -   Remove old links
    -   Save changes to the database

-   Handle appropriate error cases

-   Create a functional test for the Command

### Command Execution

Once implemented, the command can be executed with:

```
php bin/console app:update-contact
```

## Project Structure

The project follows a standard Symfony architecture with:

-   `src/Entity/`: Doctrine entities
-   `src/Repository/`: Repositories for data access
-   `src/Command/`: Console commands, including `UpdateContactCommand`

## Run Tests

The test suite uses an in-memory SQLite database (no configuration needed).

Run all tests with:

```
make test
```

Prepare the test database schema:
```
make test-db
```

Happy coding!




