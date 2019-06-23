<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Wallabag\CoreBundle\Doctrine\WallabagMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190826204730 extends WallabagMigration
{
    public function up(Schema $schema): void
    {
        $this->skipIf($schema->hasTable($this->getTable('ignore_origin_user_rule')), 'It seems that you already played this migration.');
        $this->skipIf($schema->hasTable($this->getTable('ignore_origin_instance_rule')), 'It seems that you already played this migration.');

        $userTable = $schema->createTable($this->getTable('ignore_origin_user_rule', true));
        $userTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $userTable->addColumn('config_id', 'integer');
        $userTable->addColumn('rule', 'string', ['length' => 255]);
        $userTable->addIndex(['config_id'], 'idx_config');
        $userTable->setPrimaryKey(['id']);
        $userTable->addForeignKeyConstraint($this->getTable('config'), ['config_id'], ['id'], [], 'fk_config');

        $instanceTable = $schema->createTable($this->getTable('ignore_origin_instance_rule', true));
        $instanceTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $instanceTable->addColumn('rule', 'string', ['length' => 255]);
        $instanceTable->setPrimaryKey(['id']);

        if ('postgresql' === $this->connection->getDatabasePlatform()->getName()) {
            $schema->dropSequence('ignore_origin_user_rule_id_seq');
            $schema->createSequence('ignore_origin_user_rule_id_seq');

            $schema->dropSequence('ignore_origin_instance_rule_id_seq');
            $schema->createSequence('ignore_origin_instance_rule_id_seq');
        }
    }

    public function postUp(Schema $schema): void
    {
        foreach ($this->container->getParameter('wallabag_core.default_ignore_origin_instance_rules') as $entity) {
            $previous_rule = $this->container
                ->get('doctrine.orm.default_entity_manager')
                ->getConnection()
                ->fetchArray('SELECT * FROM ' . $this->getTable('ignore_origin_instance_rule') . " WHERE rule = '" . $entity["rule"] . "'");

            if (false === $previous_rule) {
                $this->addSql('INSERT INTO ' . $this->getTable('ignore_origin_instance_rule') . " (rule) VALUES ('" . $entity["rule"] . "');");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->dropTable($this->getTable('ignore_origin_user_rule'));
        $this->dropTable($this->getTable('ignore_origin_instance_rule'));
    }
}
