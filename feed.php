<?php
/* feed.php — YML-фид для Яндекс.Директа (товарная кампания, динамика, смарт-баннеры).
   Читает data/*.json через load_products, отдаёт YML.
   Доступен по https://wergrauf.ru/feed.php — обновляется сам после синхронизации. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/admin/helpers.php';

header('Content-Type: application/xml; charset=utf-8');

const FEED_DOMAIN = 'https://wergrauf.ru';
const FEED_BRAND  = 'WERGRAUF';

// --- Экранирование для XML ---
function yml_esc(string $s): string {
	// Убираем управляющие символы 0–31, кроме tab/LF/CR (запрещены в YML)
	$s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
	return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// --- Слаг в URL (slug у нас уже безопасный, rawurlencode подстрахует) ---
function feed_slug(string $slug): string {
	return rawurlencode($slug);
}

// --- Подготовка категорий: раздел → числовой id ---
$categories = [];   // id => название
$cat_id_of  = [];   // section => id
$i = 1;
foreach (SECTION_NAMES as $section => $title) {
	$categories[$i]      = $title;
	$cat_id_of[$section] = $i;
	$i++;
}

// --- Сбор предложений ---
$offers   = [];
$seen_ids = [];

foreach (array_keys(SECTION_NAMES) as $section) {
	$cat_id   = $cat_id_of[$section];
	$base_url = FEED_DOMAIN . (SECTION_URLS[$section] ?? '/');

	foreach (load_products($section) as $p) {
		if (!empty($p['hidden'])) continue;

		$price = (int)($p['price'] ?? 0);
		if ($price <= 0) continue; // без цены в фид нельзя

		// id: артикул, иначе slug. Гарантируем уникальность
		$id = trim((string)($p['article'] ?? '')) ?: trim((string)($p['slug'] ?? ''));
		if ($id === '') continue;
		if (isset($seen_ids[$id])) {
			$seen_ids[$id]++;
			$id .= '-' . $seen_ids[$id];
		} else {
			$seen_ids[$id] = 1;
		}

		// Картинки: главная + галерея, абсолютные https, до 10 штук
		$pics = [];
		foreach (array_merge([$p['image'] ?? ''], (array)($p['gallery'] ?? [])) as $img) {
			$img = trim((string)$img);
			if ($img === '') continue;
			if ($img[0] === '/') $img = FEED_DOMAIN . $img;     // локальные → абсолютные
			if (!preg_match('~^https://~i', $img)) continue;    // только https
			if (!in_array($img, $pics, true)) $pics[] = $img;
			if (count($pics) >= 10) break;
		}

		$desc = trim(strip_tags((string)($p['description'] ?? '')));
		if (mb_strlen($desc) > 2999) $desc = mb_substr($desc, 0, 2999);

		$offers[] = [
			'id'        => $id,
			'available' => ((int)($p['stock'] ?? 0) > 0) ? 'true' : 'false',
			'url'       => $base_url . feed_slug((string)($p['slug'] ?? '')) . '/',
			'price'     => $price,
			'old_price' => ((int)($p['old_price'] ?? 0) > $price) ? (int)$p['old_price'] : 0,
			'cat_id'    => $cat_id,
			'pics'      => $pics,
			'name'      => trim((string)($p['name'] ?? '')),
			'desc'      => $desc,
		];
	}
}

// --- Вывод YML ---
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<yml_catalog date="<?= date('Y-m-d H:i') ?>">
	<shop>
		<name><?= yml_esc(FEED_BRAND) ?></name>
		<company><?= yml_esc(FEED_BRAND) ?></company>
		<url><?= FEED_DOMAIN ?>/</url>
		<currencies>
			<currency id="RUB" rate="1"/>
		</currencies>
		<categories>
<?php foreach ($categories as $cid => $cname): ?>
			<category id="<?= $cid ?>"><?= yml_esc($cname) ?></category>
<?php endforeach ?>
		</categories>
		<offers>
<?php foreach ($offers as $o): ?>
			<offer id="<?= yml_esc($o['id']) ?>" available="<?= $o['available'] ?>">
				<url><?= yml_esc($o['url']) ?></url>
				<price><?= $o['price'] ?></price>
<?php if ($o['old_price']): ?>
				<oldprice><?= $o['old_price'] ?></oldprice>
<?php endif ?>
				<currencyId>RUB</currencyId>
				<categoryId><?= $o['cat_id'] ?></categoryId>
<?php foreach ($o['pics'] as $pic): ?>
				<picture><?= yml_esc($pic) ?></picture>
<?php endforeach ?>
				<vendor><?= yml_esc(FEED_BRAND) ?></vendor>
				<name><?= yml_esc($o['name']) ?></name>
<?php if ($o['desc'] !== ''): ?>
				<description><?= yml_esc($o['desc']) ?></description>
<?php endif ?>
			</offer>
<?php endforeach ?>
		</offers>
	</shop>
</yml_catalog>