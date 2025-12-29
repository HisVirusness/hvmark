<?php
// hVmark Reference Model
// v1.5.0 - Vanilla
// (c) 2025 HisVirusness

// Typical Application:
//      Include this file in your PHP headers wherever
//      use is intended.
// include '/path/to/hvmark.php';

// Enable/Disable Table of Contents generation.
$hv_toc_enabled = true;

// hVmark Subheading <h#>
// Designate the subheading; default 2.
$hv_subhead = 2;

// TOC/Subheading Section Break
// Section break HTML output; default <hr>.
$hv_break = "<hr>";

// TOC Break Toggle
// Output a section break before and/or after generated TOC.
// 1 for top, 2 for bottom, 3 for both, 0 for none.
$hv_break_toc = 0;

// Subheading Break Toggle
// Enable/Disable break above subheading.
$hv_break_sh = true;

// House Rules on Output HTML Tabs
// Default 1.
$hv_tabcount = 1;

function hvmark(string $line): string {
    $trim = trim($line);
    if ($trim === '') return '';

    if (preg_match('/<[^>]+>/', $trim)) {
        return $trim;
    }

    $trim = str_replace(
        ['\*','\%','\_','\-','\+','\@','\^','\[]','\<','\>'],
        ['&#42;','&#37;','&#95;','&#45;', '&#43;','&#64;','&#94;','&#91;&#93;','&lt;','&gt;'],
        $trim
    );

    // anchor + fangs (link/image/YouTube)
    $trim = preg_replace_callback(
        '/@@\s*([^\^]+?)\s*\^([^^]*?)\^/u',
        function ($m) {
            $url = trim($m[1]);
            $txt = trim($m[2]);

            // IMAGE: @@img:/path.jpg[opts]^Caption^
            // attrib: "[{width}{%|px}? {left|right|center|blank}?]" (order-insensitive, both optional)
            if (stripos($url, 'img:') === 0) {
                $raw = trim(substr($url, 4));
                $src = $raw;
                $opts = '';

                if (preg_match('/^([^\[]+)\[([^\]]*)\]\s*$/', $raw, $mm)) {
                    $src  = trim($mm[1]);
                    $opts = trim($mm[2]);
                }

                if ($src === '') return '';

                $width   = '85%';
                $align   = 'center';
                if ($opts !== '') {
                    foreach (preg_split('/\s+/', $opts) as $tok) {
                        $t = strtolower(trim($tok));
                        if ($t === '') continue;

                        if (in_array($t, ['left','right','center','blank'], true)) {
                            $align = $t;
                            continue;
                        }

                        if (preg_match('/^\d+%$/', $t)) {
                            $width = $t;
                            continue;
                        }
                        if (preg_match('/^\d+px$/', $t)) {
                            $width = $t;
                            continue;
                        }
                    }
                }

                $cap = htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
                $sym = ['*','%','-','_','[',']'];
                $alt = $cap !== '' ? str_replace($sym, '', $cap)
                : basename($src);

                $srcAttr = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');

                $imgAttrs = ' src="' . $srcAttr . '" alt="' . $alt . '" loading="lazy" decoding="async"';
                if (preg_match('/^\d+%$/', $width)) {
                    $imgAttrs .= ' width="' . $width . '"';
                } else { // px
                    $px = (int)$width;
                    $imgAttrs .= ' style="width: ' . $px . 'px; height:auto; max-width:100%;"';
                }

                $imgHtml = '<img' . $imgAttrs . '>';

                $capHtml = $cap !== '' ? '<figcaption>' . $cap . '</figcaption>' : '';

                if ($align === 'blank') {
                    if (!$cap) {
                        return $imgHtml . $capHtml;
                    } else {
                        return '<figure>' . $imgHtml . $capHtml . '</figure>';
                    }
                }

                if ($align === 'left' || $align === 'right') {
                    $ta = $align === 'left' ? 'left' : 'right';
                    return '<figure style="text-align:' . $ta . ';">' . $imgHtml . $capHtml . '</figure>';
                }

                return '<figure style="text-align:center;">' . $imgHtml . $capHtml . '</figure>';
            }

            // YOUTUBE: @@ytb:VIDEOID^Caption^
            if (stripos($url, 'ytb:') === 0) {
                $id = preg_replace('/[^A-Za-z0-9_\-]/', '', substr($url, 4));
                if ($id === '') return '';
                $cap = htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
                $iframe = '<iframe src="https://www.youtube-nocookie.com/embed/' . $id .
                          '?modestbranding=1&rel=0" ' .
                          'title="YouTube video player" loading="lazy" ' .
                          'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ' .
                          'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
                $full = '<figure class="yt-container" style="text-align:center;">' . $iframe . '<center><figcaption>' . $cap . '</figcaption></center></figure>';
                $none = '<div class="yt-container" style="text-align:center;">' . $iframe . '</div>';
                if (!$cap) { $yt_out = $none; } else { $yt_out = $full; }
                return $yt_out;
            }

            // Everything else → anchor (http/https/mailto/etc)
            $sUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars($txt === '' ? $url : $txt, ENT_QUOTES, 'UTF-8');
            // External target+rel for http(s), leave mailto: & relatives alone.
            $isHttp = preg_match('#^https?://#i', $url);
            $attrs  = $isHttp ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $sUrl . '"' . $attrs . '>' . $text . '</a>';
        },
        $trim
    );

    // Soft Break: [] → <br>
    $trim = str_replace('[]', '<br>', $trim);

    // *// Heading text*: <h# id="heading-text">// Heading text</h#>
    // Remember when I said "opinionated"? Exhibit A:
    $headings = [];
    $trim = preg_replace_callback(
        '~(?m)^(?!.*<)(?<!\\\\)\*//\s*(.+?)\*(?:\s*)$~u',
        function ($m) {
            global $hv_tabcount;
            global $hv_subhead;
            global $hv_break_sh;
            global $hv_break;

            $text = $m[1];

            // slugify them IDs!
            $slug = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
            $slug = preg_replace('/[\'’]+/u', '', $slug);     // nix straight + curly apostrophes
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug); // dash up everything else
            $slug = preg_replace('/-+/', '-', $slug);         // collapse repeats
            $slug = trim($slug, '-');                         // trim edges
            if ($slug === '') $slug = 'section';

            // safety first
            $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            if ($hv_break_sh) {
                $indent = str_repeat("\t", $hv_tabcount);
                $sh_prefix = $hv_break . "\n" . $indent;
            } else {
                $sh_prefix = "";
            }

            return $sh_prefix.'<h'.$hv_subhead.' id="'.$slug.'"><span aria-hidden="true">// </span>'.$safe.'</h'.$hv_subhead.'>';
        },
        $trim
    );

    // Horizontal Rule: --- || *** || ___ → <hr> (or whatever you have $hv_break set at)
    $trim = preg_replace(
        '/^\s*(?:-{3,}|\*{3,}|_{3,})\s*$/',
        $GLOBALS['hv_break'],
        $trim
    );

    // Bold: *text*
    $trim = preg_replace(
        '/<[^>]*>(*SKIP)(*F)|(?<!\w)\*([^\*]+)\*(?!\w)/u',
        '<strong>$1</strong>',
        $trim
    );
    // Italic: %text%
    $trim = preg_replace(
        '/<[^>]*>(*SKIP)(*F)|(?<!\w)%([^%]+)%(?!\w)/u',
        '<em>$1</em>',
        $trim
    );
    // Strike: -text-
    $trim = preg_replace(
        '/<[^>]*>(*SKIP)(*F)|(?<!\w)-(?!-)(.+?)(?<!-)-(?!\w)/u',
        '<s>$1</s>',
        $trim
    );
    // Underline: _text_
    $trim = preg_replace(
        '/<[^>]*>(*SKIP)(*F)|(?<!\w)_([^_]+)_(?!\w)/u',
        '<u>$1</u>',
        $trim
    );
    return $trim;
}

// HTML Output
// This is the function that uses hVmark to output the HTML line by line.
// The vanilla parser itself handles single-line text formatting.
// This wraps everything in paragraphs and handles the lists.
// Typical Application:
//      "$hvmark_input" is the entire declared page/post in hVmark that'll
//      be output to HTML in the workflow.
// $lines = preg_split('/\R/', $hvmark_input, -1, PREG_SPLIT_NO_EMPTY);
// $output = hvmark_web($lines);
// hvmark_gentoc($output);
// echo $output;
function hvmark_web(array $lines): string {
    global $hv_tabcount;
    $indent = str_repeat("\t", $hv_tabcount);

    $out = '';
    $i = 0; $n = count($lines);

    while ($i < $n) {
        $raw = rtrim($lines[$i]);
        if ($raw === '') { $i++; continue; }

        if (preg_match('/^\[\s*([+\-])\s*\]\s*(.*)$/u', $raw, $m)) {
            $kind = $m[1];
            $items = [];

            while ($i < $n && preg_match('/^\[\s*' . preg_quote($kind,'/') . '\s*\]\s*(.*)$/u', rtrim($lines[$i]), $mm)) {
                $items[] = $mm[1];
                $i++;
            }

            $cls = ($kind === '+') ? 'list-style-type: square;' : 'list-style-type: none;';
            $out .= $indent . "<ul style='" . $cls . "'>" . "\n";
            foreach ($items as $it) {
                $out .= $indent . "\t<li>" . hvmark($it) . "</li>" . "\n";
            }
            $out .= $indent . "</ul>\n";
            continue;
        }

        $html = hvmark($raw);
        $clean = trim($html);

        if ($clean === '') { $i++; continue; }

        if (preg_match(
            '/^\s*<(?:center|div|ul|ol|hr|blockquote|iframe|table|pre|h[1-6]|figure|p|nav|!--)\b/i',
            $clean
        ) ||
        preg_match('/^\s*<img\b[^>]*>\s*$/i', $clean)
        ) {
            $out .= $indent . $clean . "\n";
            $i++;
            continue;
        }

        $out .= $indent . "<p>" . $html . "</p>\n";
        $i++;
    }
    return $out;
}

function hvmark_gentoc(&$html, array $opts = []) {
    global $hv_subhead;
    global $hv_tabcount;
    global $hv_toc_enabled;
    global $hv_break;
    global $hv_break_toc;

    if($hv_toc_enabled) {
        $min         = $opts['min']         ?? 2;
        $placeholder = $opts['placeholder'] ?? '<!--HV_TOC-->';
        $navClass    = $opts['class']       ?? 'toc';
        $ariaLabel   = $opts['label']       ?? 'On this page';
        $INDENT_UNIT = $opts['indent']      ?? "\t";

        $base = is_numeric($hv_tabcount) ? (int)$hv_tabcount : 0;
        $I = function(int $delta = 0) use ($base, $INDENT_UNIT) {
            return str_repeat($INDENT_UNIT, max(0, $base + $delta));
        };

        $re = '~<h'.$hv_subhead.'\b[^>]*\bid=["\']([^"\']+)["\'][^>]*>(.*?)</h'.$hv_subhead.'>~is';
        if (!preg_match_all($re, $html, $m, PREG_SET_ORDER)) {
            if (strpos($html, $placeholder) !== false) $html = str_replace($placeholder, '', $html);
            return false;
        }

        $items = [];
        foreach ($m as $hit) {
            $id = $hit[1];
            $label = trim(strip_tags($hit[2]));
            if ($id !== '' && $label !== '') {
                $items[] = ['id' => $id, 'label' => $label];
            }
        }
        if (count($items) < $min) {
            if (strpos($html, $placeholder) !== false) $html = str_replace($placeholder, '', $html);
            return false;
        }

        if ($hv_break_toc === 1 || $hv_break_toc === 3){
            $toc  = $hv_break . "\n"
            . $I(0) . '<nav aria-label="'.htmlspecialchars($ariaLabel, ENT_QUOTES).'" class="'.htmlspecialchars($navClass, ENT_QUOTES).'">' . "\n";
        } else {
            $toc  = '<nav aria-label="'.htmlspecialchars($ariaLabel, ENT_QUOTES).'" class="'.htmlspecialchars($navClass, ENT_QUOTES).'">' . "\n";
        }
        $toc .= $I(0) . '<ul style="list-style-type: none;">' . "\n";
        foreach ($items as $it) {
            $bracket = $it['label'];
            $bracket = substr($bracket, 3);
            $toc .= $I(1) . '<li><span aria-hidden="true">:: </span><a href="#'.htmlspecialchars($it['id'], ENT_QUOTES).'">'
            . $bracket
            . '</a></li>' . "\n";
        }
        $toc .= $I(0) . '</ul>' . "\n";
        if (strpos($html, $placeholder) !== false) {
            if ($hv_break_toc === 2 || $hv_break_toc === 3) {
                $toc .= $I(0) . '</nav>' . "\n" . $I(0) . $hv_break;
            } else {
                $toc .= $I(0) . '</nav>';
            }
        } else {
            if ($hv_break_toc === 2 || $hv_break_toc === 3) {
                $toc .= $I(0) . '</nav>' . "\n" . $I(0) . $hv_break . "\n";
            } else {
                $toc .= $I(0) . '</nav>' . "\n";
            }
        }

        if (strpos($html, $placeholder) !== false) {
            $html = str_replace($placeholder, $toc, $html);
            return true;
        }

        $h12 = '/(<h(?:1|2)\b[^>]*>.*?<\/h(?:1|2)>\s*(?:<br[^>]*>\s*)?)/is';
        $new  = preg_replace($h12, '$1' . $toc . $I(0), $html, 1, $count);
        if ($count > 0) { $html = $new; return true; }

        $html = $I(0) . $toc . $html;
        return true;
    }
}

?>
