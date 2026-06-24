import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'FullStuck.php',
  description: 'The Zero-Config, AI-Friendly, Single-File PHP Framework',
  base: '/', // Base URL path for GitHub Pages (custom domain)
  themeConfig: {
    logo: '🚀',
    siteTitle: 'FullStuck.php',
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Dokumentasi v0.2.0', link: '/v0.2.0' },
      { text: 'Cheatsheet AI', link: '/v0.2.0_cheatsheet' },
      { text: 'Panduan AI', link: '/ai-setup' },
      { text: 'Changelog', link: 'https://github.com/milio48/fullstuck/blob/main/CHANGELOG.md' }
    ],
    sidebar: [
      {
        text: 'Mulai Cepat',
        items: [
          { text: 'Selamat Datang', link: '/' },
          { text: 'Dokumentasi v0.2.0 (Terbaru)', link: '/v0.2.0' },
          { text: 'Cheatsheet AI (v0.2.0)', link: '/v0.2.0_cheatsheet' },
          { text: 'SOP & Panduan Setup AI', link: '/ai-setup' }
        ]
      },
      {
        text: 'Konsep & Panduan Lanjut',
        items: [
          { text: 'Arsitektur Framework', link: '/ARCHITECTURE' },
          { text: 'Pengembangan Plugin', link: '/PLUGIN_DEVELOPMENT' }
        ]
      },
      {
        text: 'Arsip',
        items: [
          { text: 'Dokumentasi v0.1.0 (Legacy)', link: '/v0.1.0' }
        ]
      }
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/milio48/fullstuck' }
    ],
    footer: {
      message: 'Dibuat dengan ❤️ untuk ekosistem PHP yang lebih sederhana.',
      copyright: 'Copyright © 2026-present milio48'
    }
  }
})
