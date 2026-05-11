import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import 'package:uuid/uuid.dart';

class DatabaseHelper {
  static final DatabaseHelper instance = DatabaseHelper._init();
  static Database? _database;

  DatabaseHelper._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('wiro_expense.db');
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);

    final db = await openDatabase(
      path,
      version: 2,
      onCreate: _createDB,
      onUpgrade: _upgradeDB,
    );
    await _ensureOtherCategoryExists(db);
    return db;
  }

  Future<void> _upgradeDB(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) {
      await db.execute("ALTER TABLE accounts ADD COLUMN balance INTEGER NOT NULL DEFAULT 0;");
    }
  }

  Future<void> _ensureOtherCategoryExists(Database db) async {
    final uuid = const Uuid();
    for (var type in ['personal', 'company']) {
      final List<Map<String, dynamic>> maps = await db.query(
        'categories',
        where: 'name = ? AND type = ?',
        whereArgs: ['Other', type],
      );
      if (maps.isEmpty) {
        await db.insert('categories', {
          'id': uuid.v4(),
          'name': 'Other',
          'type': type,
          'sync_status': 'synced'
        });
      }
    }
  }

  Future _createDB(Database db, int version) async {
    const uuidType = 'TEXT PRIMARY KEY';
    const textType = 'TEXT NOT NULL';
    const integerType = 'INTEGER NOT NULL';

    await db.execute('''
      CREATE TABLE accounts (
        id $uuidType,
        name $textType,
        type $textType, -- 'personal' or 'company'
        balance INTEGER NOT NULL DEFAULT 0,
        sync_status TEXT DEFAULT 'pending'
      )
    ''');

    await db.execute('''
      CREATE TABLE categories (
        id $uuidType,
        name $textType,
        type $textType, -- 'personal' or 'company'
        sync_status TEXT DEFAULT 'pending'
      )
    ''');

    await db.execute('''
      CREATE TABLE expenses (
        id $uuidType,
        amount $integerType,
        description TEXT,
        category_id TEXT,
        account_id TEXT,
        date $textType,
        type $textType, -- 'personal' or 'company'
        sync_status TEXT DEFAULT 'pending',
        FOREIGN KEY (category_id) REFERENCES categories (id),
        FOREIGN KEY (account_id) REFERENCES accounts (id)
      )
    ''');

    // Pre-populate data dihapus agar tidak bentrok dengan data server
  }

  // Generic Query
  Future<List<Map<String, dynamic>>> queryAll(String table) async {
    final db = await instance.database;
    return await db.query(table);
  }

  // Get Expenses with joins
  Future<List<Map<String, dynamic>>> getExpensesWithDetails({String? type, DateTime? startDate, DateTime? endDate}) async {
    final db = await instance.database;
    List<String> conditions = [];
    if (type != null) conditions.add("e.type = '$type'");
    if (startDate != null) conditions.add("e.date >= '${startDate.toIso8601String()}'");
    if (endDate != null) conditions.add("e.date <= '${endDate.toIso8601String()}'");
    
    String whereClause = conditions.isNotEmpty ? "WHERE ${conditions.join(' AND ')}" : "";
    
    return await db.rawQuery('''
      SELECT e.*, c.name as category, a.name as account
      FROM expenses e
      LEFT JOIN categories c ON e.category_id = c.id
      LEFT JOIN accounts a ON e.account_id = a.id
      $whereClause
      ORDER BY date DESC
    ''');
  }

  // Insert Expense
  Future<String> insertExpense(Map<String, dynamic> data) async {
    final db = await instance.database;
    final id = const Uuid().v4();
    await db.insert('expenses', {
      ...data,
      'id': id,
      'sync_status': 'pending',
    });
    return id;
  }

  // Update Expense
  Future<int> updateExpense(String id, Map<String, dynamic> data) async {
    final db = await instance.database;
    return await db.update(
      'expenses',
      {...data, 'sync_status': 'pending'},
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  // Delete Expense
  Future<int> deleteExpense(String id) async {
    final db = await instance.database;
    return await db.delete(
      'expenses',
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  // Get categories and accounts for a specific type
  Future<List<Map<String, dynamic>>> getCategories(String type) async {
    final db = await instance.database;
    return await db.query('categories', where: 'type = ?', whereArgs: [type]);
  }

  Future<List<Map<String, dynamic>>> getAccounts(String type) async {
    final db = await instance.database;
    return await db.query('accounts', where: 'type = ?', whereArgs: [type]);
  }

  // Master Data CRUD
  Future<String> insertAccount(Map<String, dynamic> data) async {
    final db = await instance.database;
    final id = const Uuid().v4();
    await db.insert('accounts', {
      ...data,
      'id': id,
      'sync_status': 'pending',
    });
    return id;
  }

  Future<int> updateAccount(String id, Map<String, dynamic> data) async {
    final db = await instance.database;
    return await db.update(
      'accounts',
      {...data, 'sync_status': 'pending'},
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  Future<int> deleteAccount(String id) async {
    final db = await instance.database;
    return await db.delete('accounts', where: 'id = ?', whereArgs: [id]);
  }

  Future<String> insertCategory(Map<String, dynamic> data) async {
    final db = await instance.database;
    final id = const Uuid().v4();
    await db.insert('categories', {
      ...data,
      'id': id,
      'sync_status': 'pending',
    });
    return id;
  }

  Future<int> updateCategory(String id, Map<String, dynamic> data) async {
    final db = await instance.database;
    return await db.update(
      'categories',
      {...data, 'sync_status': 'pending'},
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  Future<int> deleteCategory(String id) async {
    final db = await instance.database;
    return await db.delete('categories', where: 'id = ?', whereArgs: [id]);
  }

  // Statistics Queries
  Future<List<Map<String, dynamic>>> getCategoryExpenses(String type, {DateTime? startDate, DateTime? endDate}) async {
    final db = await instance.database;
    List<String> conditions = ["e.type = ?"];
    List<dynamic> args = [type];
    
    if (startDate != null) {
      conditions.add("e.date >= ?");
      args.add(startDate.toIso8601String());
    }
    if (endDate != null) {
      conditions.add("e.date <= ?");
      args.add(endDate.toIso8601String());
    }

    return await db.rawQuery('''
      SELECT c.name as category, SUM(e.amount) as total
      FROM expenses e
      JOIN categories c ON e.category_id = c.id
      WHERE ${conditions.join(' AND ')}
      GROUP BY c.id
    ''', args);
  }

  Future<List<Map<String, dynamic>>> getDailyExpensesTrend(String type, {DateTime? startDate, DateTime? endDate}) async {
    final db = await instance.database;
    List<String> conditions = ["type = ?"];
    List<dynamic> args = [type];
    
    if (startDate != null) {
      conditions.add("date >= ?");
      args.add(startDate.toIso8601String());
    }
    if (endDate != null) {
      conditions.add("date <= ?");
      args.add(endDate.toIso8601String());
    }

    String limitClause = (startDate == null && endDate == null) ? "LIMIT 7" : "";

    return await db.rawQuery('''
      SELECT strftime('%Y-%m-%d', date) as day, SUM(amount) as total
      FROM expenses
      WHERE ${conditions.join(' AND ')}
      GROUP BY day
      ORDER BY day ASC
      $limitClause
    ''', args);
  }
}
