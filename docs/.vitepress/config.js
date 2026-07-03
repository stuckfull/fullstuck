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
      { 
        text: 'Versi',
        items: [
          { text: 'v0.3.0 (Terbaru)', link: '/v0.3/' },
          { text: 'v0.2.0 (Legacy)', link: '/v0.2/' }
        ]
      },
      { text: 'Cheatsheet AI', link: '/v0.3.0_cheatsheet' },
      { text: 'Panduan AI', link: '/ai-setup' },
      { text: 'Changelog', link: 'https://github.com/milio48/fullstuck/blob/main/CHANGELOG.md' }
    ],
    sidebar: {
      '/v0.3/': [
        {
          text: 'Mulai Cepat',
          items: [
            { text: 'Pengenalan & Instalasi', link: '/v0.3/01-getting-started' },
            { text: 'Cheatsheet AI Cepat', link: '/v0.3.0_cheatsheet' },
            { text: 'Versi Lengkap (All-in-One)', link: '/v0.3/FULL' }
          ]
        },
        {
          text: 'Core Features',
          items: [
            { text: 'Routing & Middleware', link: '/v0.3/02-routing' },
            { text: 'Database & Query', link: '/v0.3/03-database' },
            { text: 'Security & Validation', link: '/v0.3/04-security' },
            { text: 'DOM Templating', link: '/v0.3/05-templates' }
          ]
        },
        {
          text: 'Front-End',
          items: [
            { text: 'FST Agent (Hybrid SPA)', link: '/v0.3/06-fst-agent' }
          ]
        },
        {
          text: 'Observability',
          items: [
            { text: 'Logging & Error Handling', link: '/v0.3/07-logging' }
          ]
        }
      ],
      '/': [
        {
          text: 'Versi Terbaru',
          items: [
            { text: 'Dokumentasi v0.3.0', link: '/v0.3/' }
          ]
        },
        {
          text: 'Konsep & Panduan Lanjut',
          items: [
            { text: 'SOP & Panduan Setup AI', link: '/ai-setup' },
            { text: 'Arsitektur Framework', link: '/ARCHITECTURE' },
            { text: 'Pengembangan Plugin', link: '/PLUGIN_DEVELOPMENT' }
          ]
        },
        {
          text: 'Arsip (Legacy)',
          items: [
            { text: 'Dokumentasi v0.2.0', link: '/v0.2/' },
            { text: 'Cheatsheet v0.2.0', link: '/v0.2/cheatsheet' },
            { text: 'Dokumentasi v0.1.0', link: '/v0.1/' }
          ]
        }
      ]
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/milio48/fullstuck' }
    ],
    footer: {
      message: 'Dibuat dengan ❤️ untuk ekosistem PHP yang lebih sederhana.',
      copyright: 'Copyright © 2026-present milio48'
    }
  }
})
