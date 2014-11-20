<?php

namespace Mismatch\ORM;

use Mismatch\ORM\Expression\Composite;
use Doctrine\DBAL\Types\Type;
use Mockery;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->conn = Mockery::mock('Doctrine\DBAL\Connection');
        $this->table = ['authors' => 'author'];
        $this->pk = (object) ['key' => 'id'];

        $this->subject = new Query($this->conn, $this->table, $this->pk);
    }

    public function test_raw_select()
    {
        $this->assertQuery(
            'SELECT author.* FROM authors AS author WHERE author.name = ?',
            ['test']);

        $this->subject->raw(
            'SELECT author.* FROM authors AS author WHERE author.name = ?',
            ['test']);
    }

    public function test_raw_modification()
    {
        $this->assertQuery(
            'DELETE FROM authors AS author WHERE author.name = ?',
            ['test']);

        $this->subject->raw(
            'DELETE FROM authors AS author WHERE author.name = ?',
            ['test']);
    }

    public function test_bareAll()
    {
        $this->assertQuery(
            'SELECT author.* FROM authors AS author', []);

        $this->subject->all();
    }

    public function test_aggregateAll()
    {
        $this->subject->select(['COUNT(*)' => 'count']);

        $this->assertQuery(
            'SELECT COUNT(*) AS count FROM authors AS author', []);

        $this->subject->all();
    }

    public function test_find_id()
    {
        $this->assertQuery(
            'SELECT author.* FROM authors AS author '.
            'WHERE author.id = ? LIMIT 1', [1], [true]);
        $this->assertLimit(1);

        $this->subject->find(1);
    }

    /**
     * @expectedException  Mismatch\ORM\Exception\ModelNotFoundException
     */
    public function test_find_missing()
    {
        $this->assertQuery(
            'SELECT author.* FROM authors AS author '.
            'WHERE author.id = ? LIMIT 1', [1]);
        $this->assertLimit(1);

        $this->subject->find(1);
    }

    public function test_where_withArray()
    {
        $this->subject->where(['name' => 'test']);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author '.
            'WHERE author.name = ?', ['test']);

        $this->subject->all();
    }

    public function test_where_withArrayMultiple()
    {
        $this->subject->whereAny(['name' => 'test', 'id' => 1]);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'WHERE author.name = ? OR author.id = ?',
            ['test', 1]);

        $this->subject->all();
    }

    public function test_where_withString()
    {
        $this->subject->where('name = ?', ['test']);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'WHERE name = ?', ['test']);

        $this->subject->all();
    }

    public function test_exclude_withArray()
    {
        $this->subject->exclude(['name' => 'test']);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'WHERE NOT (author.name = ?)', ['test']);

        $this->subject->all();
    }

    public function test_excludeAny_withArrayMultiple()
    {
        $this->subject->excludeAny(['name' => 'test', 'id' => 1]);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'WHERE NOT (author.name = ? OR author.id = ?)',
            ['test', 1]);

        $this->subject->all();
    }

    public function test_exclude_withString()
    {
        $this->subject->exclude('name = ?', ['test']);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'WHERE NOT (name = ?)', ['test']);

        $this->subject->all();
    }

    public function test_having_withArray()
    {
        $this->subject->having(['COUNT(bonus)' => 1000]);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'HAVING COUNT(bonus) = ?', [1000]);

        $this->subject->all();
    }

    public function test_having_withArrayMultiple()
    {
        $this->subject->havingAny(['name' => 'test', 'id' => 1]);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author '.
            'HAVING author.name = ? OR author.id = ?',
            ['test', 1]);

        $this->subject->all();
    }

    public function test_having_withString()
    {
        $this->subject->having('name = ?', ['test']);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author '.
            'HAVING name = ?', ['test']);

        $this->subject->all();
    }

    public function test_limit()
    {
        $this->subject->limit(1);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author LIMIT 1', []);
        $this->assertLimit(1);

        $this->subject->all();
    }

    public function test_order()
    {
        $this->subject->order([
            'name' => 'asc',
            'id' => 'desc',
       ]);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'ORDER BY author.name ASC, author.id DESC', []);

        $this->subject->all();
    }

    public function test_group()
    {
        $this->subject->group(['name', 'id']);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'GROUP BY author.name, author.id', []);

        $this->subject->all();
    }

    public function test_joins_defaultInnerJoin()
    {
        $this->subject->join('books b');

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'INNER JOIN books b', []);

        $this->subject->all();
    }

    public function test_joins_withArray()
    {
        $this->subject->join('books b', ['author.id' => 'b.author_id']);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'INNER JOIN books b ON (author.id = b.author_id)', []);

        $this->subject->all();
    }

    public function test_joins_withExpression()
    {
        $expr = new Composite();
        $expr->all(['b.author_id' => 1]);

        $this->subject->join('books b', $expr);

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'INNER JOIN books b ON (b.author_id = ?)', [1]);

        $this->subject->all();
    }

    public function test_joins_customJoin()
    {
        $this->subject->join('LEFT OUTER JOIN books b');

        $this->assertQuery(
            'SELECT author.* FROM authors AS author ' .
            'LEFT OUTER JOIN books b', []);

        $this->subject->all();
    }

    public function test_update()
    {
        $this->assertQuery(
            'UPDATE authors SET role_id = ?', [1]);

        $this->subject->update(['role_id' => 1]);
    }

    public function test_update_where()
    {
        $this->assertQuery(
            'UPDATE authors SET role_id = ? WHERE author.id = ?',
            [1, 2]);

        $this->subject->where(2)->update(['role_id' => 1]);
    }

    public function test_insert()
    {
        $this->assertQuery(
            'INSERT INTO authors (email) VALUES (?)',
            ['foo@example.com']);

        $this->subject->insert(['email' => 'foo@example.com']);
    }

    public function test_delete()
    {
        $this->assertQuery(
            'DELETE FROM authors', []);

        $this->subject->delete();
    }

    public function test_delete_multipleFrom()
    {
        $this->subject->from('books');

        $this->assertQuery(
            'DELETE FROM authors, books', []);

        $this->subject->delete();
    }

    public function test_delete_withId()
    {
        $this->assertQuery(
            'DELETE FROM authors WHERE author.id = ?', [1]);

        $this->subject->delete(1);
    }

    public function test_delete_where_isCompiled()
    {
        $this->subject->where(['name' => 'test']);

        $this->assertQuery(
            'DELETE FROM authors WHERE author.name = ?',
            ['test']);

        $this->subject->delete();
    }

    public function test_delete_limit_isCompiled()
    {
        $this->subject->limit(1);

        $this->assertQuery(
            'DELETE FROM authors LIMIT 1', []);
        $this->assertLimit(1);

        $this->subject->delete();
    }

    public function test_delete_compilesJoins()
    {
        $this->subject->from('books')->join('books');

        $this->assertQuery(
            'DELETE FROM authors, books INNER JOIN books', []);

        $this->subject->delete();
    }

    public function assertLimit($count)
    {
        $this->conn
            ->shouldReceive('getDatabasePlatform->modifyLimitQuery')
            ->andReturnUsing(function ($query) use ($count) {
                return $query . ' LIMIT ' . $count;
            });
    }

    public function assertQuery($sql, $params, $result = [])
    {
        $stmt = Mockery::mock('Doctrine\DBAL\Driver\Statement');
        $stmt->shouldReceive('columnCount')
            ->andReturn(5);
        $stmt->shouldReceive('rowCount')
            ->andReturn(1);
        $stmt->shouldReceive('fetch')
            ->andReturn($result);

        $this->conn
            ->shouldReceive('executeQuery')
            ->with($sql, $params)
            ->andReturn($stmt);

        $this->conn
            ->shouldReceive('executeUpdate')
            ->with($sql, $params)
            ->andReturn(1);
    }
}
