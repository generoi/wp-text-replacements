<div class="wrap">
    <h1>Text Replacements</h1>

    <?php if ($message ?? null) : ?>
        <div id="message" class="updated">
            <p><?php echo $message; ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <table class="form-table">
            <thead>
                <tr>
                    <th>Original text</th>
                    <th>Replacement</th>
                    <th>Domain</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($strings as $id => $info) : ?>
                    <tr>
                        <td><?php echo esc_html($info['search'] ?? ''); ?></td>
                        <td>
                            <textarea cols="40" rows="2" name="strings[<?php echo $id; ?>][replace]"><?php echo esc_textarea($info['replace'] ?? ''); ?></textarea>
                        </td>
                        <td><?php echo esc_html($info['domain'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


        <?php wp_nonce_field('text-replacements-nonce'); ?>
        <?php submit_button(); ?>
    </form>
</div>
