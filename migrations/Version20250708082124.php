<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250708082124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'First migration for the exo7 project, creating address, contact, and organization tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE address (id SERIAL NOT NULL, street VARCHAR(255) DEFAULT NULL, street2 VARCHAR(255) DEFAULT NULL, manual_zip_code VARCHAR(255) DEFAULT NULL, manual_city VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip_code VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN address.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE contact (id SERIAL NOT NULL, pp_identifier VARCHAR(11) DEFAULT NULL, pp_identifier_type SMALLINT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, family_name VARCHAR(255) NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN contact.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN contact.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN contact.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE contact_organizations (contact_id INT NOT NULL, organization_id INT NOT NULL, PRIMARY KEY(contact_id, organization_id))');
        $this->addSql('CREATE INDEX IDX_63E45FF8E7A1254A ON contact_organizations (contact_id)');
        $this->addSql('CREATE INDEX IDX_63E45FF832C8A3DE ON contact_organizations (organization_id)');
        $this->addSql('CREATE TABLE organization (id SERIAL NOT NULL, address_id INT DEFAULT NULL, technical_id VARCHAR(60) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, email_address VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(15) DEFAULT NULL, private BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C1EE637CF5B7AF75 ON organization (address_id)');
        $this->addSql('COMMENT ON COLUMN organization.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN organization.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN organization.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE contact_organizations ADD CONSTRAINT FK_63E45FF8E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contact_organizations ADD CONSTRAINT FK_63E45FF832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637CF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE contact_organizations DROP CONSTRAINT FK_63E45FF8E7A1254A');
        $this->addSql('ALTER TABLE contact_organizations DROP CONSTRAINT FK_63E45FF832C8A3DE');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_C1EE637CF5B7AF75');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE contact_organizations');
        $this->addSql('DROP TABLE organization');
    }
}
