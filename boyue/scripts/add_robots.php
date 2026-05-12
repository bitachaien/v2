<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dbConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: 3306,
    'database' => getenv('DB_DATABASE') ?: 'boyue',
    'username' => getenv('DB_USERNAME') ?: 'boyue',
    'password' => getenv('DB_PASSWORD') ?: 'nhDHjmHJTpmKsWye',
];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "数据库连接成功\n";
} catch (PDOException $e) {
    echo "数据库连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

$avatarDir = '/uploads/jqr/';
$avatarPath = __DIR__ . '/../public' . $avatarDir;
$avatars = [];
if (is_dir($avatarPath)) {
    $files = scandir($avatarPath);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
            $avatars[] = $avatarDir . $file;
        }
    }
}
echo "找到 " . count($avatars) . " 个头像文件\n";

if (empty($avatars)) {
    echo "没有找到头像文件!\n";
    exit(1);
}

$surnames = ['王', '李', '张', '刘', '陈', '杨', '赵', '黄', '周', '吴', '徐', '孙', '胡', '朱', '高', '林', '何', '郭', '马', '罗', '梁', '宋', '郑', '谢', '韩', '唐', '冯', '于', '董', '萧', '程', '曹', '袁', '邓', '许', '傅', '沈', '曾', '彭', '吕', '苏', '卢', '蒋', '蔡', '贾', '丁', '魏', '薛', '叶', '阎'];
$maleNames = ['伟', '强', '磊', '洋', '勇', '军', '杰', '涛', '超', '明', '刚', '平', '辉', '鹏', '华', '飞', '鑫', '波', '斌', '宇', '浩', '凯', '健', '俊', '帅', '龙', '林', '峰', '威', '彬'];
$femaleNames = ['芳', '娜', '敏', '静', '丽', '艳', '娟', '霞', '秀', '玲', '桂', '英', '梅', '莉', '萍', '红', '琴', '云', '珍', '华', '慧', '婷', '雪', '晶', '洁', '欣', '颖', '蕾', '倩', '琳'];

function generateNickname($surnames, $maleNames, $femaleNames) {
    $surname = $surnames[array_rand($surnames)];
    $isMale = rand(0, 1);
    $names = $isMale ? $maleNames : $femaleNames;
    $name = $names[array_rand($names)];
    $suffix = rand(10, 99);
    return $surname . $name . $suffix;
}

$stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(username, 7) AS UNSIGNED)) as max_num FROM caipiao_member WHERE username LIKE 'robot_%'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$startNum = ($result['max_num'] ?? 0) + 1;
echo "从 robot_{$startNum} 开始创建\n";

$sql = "INSERT INTO caipiao_member (
    parentid, groupid, jinjijilu, username, nickname, proxy, isnb, email, phone,
    userbankname, password, tradepassword, sex, balance, xima, fandian, tel, face,
    loginip, iparea, regtime, regip, source, logintime, loginsource, onlinetime,
    islock, is_robot, birthday, record, yebmoney, money, yebtime, yeblixi, dyebmoney, ngbalance
) VALUES (
    0, 1, 1, :username, :nickname, 0, 0, '', '',
    '', :password, 'd93a5def7511da3d0f2d171d9c344e91', :sex, :balance, 0, '', '', :face,
    '', '', :regtime, '', '系统', 0, '', 0,
    0, 1, '2000-01-01', 0, 0, 0, 0, 0, 0, 0
)";

$stmt = $pdo->prepare($sql);

$successCount = 0;
$password = md5('robot123456'); 

for ($i = 0; $i < 100; $i++) {
    $robotNum = $startNum + $i;
    $username = "robot_{$robotNum}";
    $nickname = generateNickname($surnames, $maleNames, $femaleNames);
    $avatar = $avatars[array_rand($avatars)];
    $sex = rand(1, 2); 
    $balance = rand(5000, 20000); 
    
    try {
        $stmt->execute([
            ':username' => $username,
            ':nickname' => $nickname,
            ':password' => $password,
            ':sex' => $sex,
            ':balance' => $balance,
            ':face' => $avatar,
            ':regtime' => time(),
        ]);
        $successCount++;
        echo "创建机器人: {$username} ({$nickname})\n";
    } catch (PDOException $e) {
        echo "创建失败 {$username}: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "成功创建 {$successCount} 个机器人\n";
echo "用户名范围: robot_{$startNum} ~ robot_" . ($startNum + $successCount - 1) . "\n";
echo "========================================\n";

$stmt = $pdo->query("SELECT COUNT(*) as total FROM caipiao_member WHERE is_robot = 1");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "机器人总数: {$result['total']}\n";

