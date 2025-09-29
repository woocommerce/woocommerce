import * as path from 'path';
import * as fs from 'fs';
import type { LoadContext, Plugin } from '@docusaurus/types';

/**
 * A Docusaurus plugin to generate LLM-friendly documentation following
 * the llmtxt.org standard
 * 
 * @param context - Docusaurus context
 * @param options - Plugin options
 * @returns Plugin object
 */
export default function llmsTxtPlugin(
    context: LoadContext,
  ): Plugin<void> {

  return {
    name: 'llms-txt',

    /**
     * Generates LLM-friendly documentation files after the build is complete
     */
    async postBuild(): Promise<void> {
      console.log('Generating LLM-friendly documentation...');
     
      try {
        // Use relative path from plugin location to find the docs directory
        const pluginDir = __dirname;
        const docsDir = path.resolve(pluginDir, '../..');
        const buildDir = context.outDir;
        
        console.log(`Scanning docs directory: ${docsDir}`);
        
        // Get all markdown files recursively, excluding _docu-tools
        const markdownFiles = await getAllMarkdownFiles(docsDir);
        
        console.log(`Found ${markdownFiles.length} markdown files`);
        
        // Generate llms.txt (file list with descriptions)
        await generateFileList(markdownFiles, buildDir);
        
        // Generate llms-full.txt (combined content)
        await generateCombinedContent(markdownFiles, buildDir);
        
        console.log(`Generated LLM documentation files in ${buildDir}`);
      } catch (err) {
        console.error('Error generating LLM documentation:', err);
      }
    },
  };
}

/**
 * Recursively gets all markdown files in a directory, excluding _docu-tools
 */
async function getAllMarkdownFiles(dir: string): Promise<Array<{ path: string; relativePath: string; content: string; title: string }>> {
  const files: Array<{ path: string; relativePath: string; content: string; title: string }> = [];
  
  async function scanDirectory(currentDir: string, relativePath: string = ''): Promise<void> {
    const entries = await fs.promises.readdir(currentDir, { withFileTypes: true });
    
    for (const entry of entries) {
      const fullPath = path.join(currentDir, entry.name);
      const entryRelativePath = path.join(relativePath, entry.name);
      
      if (entry.isDirectory()) {
        // Skip _docu-tools directory
        if (entry.name === '_docu-tools') {
          continue;
        }
        await scanDirectory(fullPath, entryRelativePath);
      } else if (
        entry.isFile() &&
        (entry.name.endsWith('.md') || entry.name.endsWith('.mdx'))
      ) {
        try {
          const content = await fs.promises.readFile(fullPath, 'utf-8');
          const title = extractTitle(content, entry.name);
          files.push({
            path: fullPath,
            relativePath: entryRelativePath,
            content,
            title
          });
        } catch (err) {
          console.warn(`Failed to read markdown file: ${fullPath}`, err);
        }
      }
    }
  }
  
  await scanDirectory(dir);
  return files;
}

/**
 * Extracts title from markdown content or uses filename as fallback
 */
function extractTitle(content: string, filename: string): string {
  // Try to find the first h1 heading
  const h1Match = content.match(/^#\s+(.+)$/m);
  if (h1Match) {
    return h1Match[1].trim();
  }
  
  // Try to find title in frontmatter
  const frontmatterMatch = content.match(/^---\s*\n([\s\S]*?)\n---\s*\n/);
  if (frontmatterMatch) {
    const titleMatch = frontmatterMatch[1].match(/^title:\s*(.+)$/m);
    if (titleMatch) {
      return titleMatch[1].trim();
    }
  }
  
  // Fallback to filename without extension
  return path.basename(filename, '.md').replace(/[-_]/g, ' ');
}

/**
 * Generates llms.txt file with file list and descriptions
 */
async function generateFileList(files: Array<{ path: string; relativePath: string; content: string; title: string }>, buildDir: string): Promise<void> {
  const outputPath = path.join(buildDir, 'llms.txt');
  let content = '# WooCommerce Documentation\n\n';
  content += 'This file contains a list of all documentation files available in the WooCommerce documentation.\n\n';
  
  for (const file of files) {
    const description = extractDescription(file.content);
    const posixPath = file.relativePath.split(path.sep).join('/'); // ensure forward slashes
    const url = `https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/docs/${posixPath}`;
    content += `[${file.title}](${url}) ${description}\n\n`;
  }
  
  await fs.promises.writeFile(outputPath, content, 'utf-8');
  console.log(`Generated ${outputPath}`);
}

/**
 * Generates llms-full.txt file with combined content
 */
async function generateCombinedContent(files: Array<{ path: string; relativePath: string; content: string; title: string }>, buildDir: string): Promise<void> {
  const outputPath = path.join(buildDir, 'llms-full.txt');
  let content = '# WooCommerce Documentation - Complete\n\n';
  content += 'This file contains the complete content of all documentation files combined.\n\n';
  
  for (const file of files) {
    content += `## ${file.title}\n\n`;
    content += `*Source: ${file.relativePath}*\n\n`;
    
    // Remove frontmatter from the content
    const contentWithoutFrontmatter = removeFrontmatter(file.content);
    content += contentWithoutFrontmatter;
    content += '\n\n---\n\n';
  }
  
  await fs.promises.writeFile(outputPath, content, 'utf-8');
  console.log(`Generated ${outputPath}`);
}

/**
 * Extracts a brief description from markdown content
 */
function extractDescription(content: string): string {
  // Remove frontmatter
  let cleanContent = content.replace(/^---\s*\n[\s\S]*?\n---\s*\n/, '');
  
  // Remove headers
  cleanContent = cleanContent.replace(/^#{1,6}\s+.+$/gm, '');
  
  // Get first paragraph (non-empty line)
  const lines = cleanContent.split('\n').filter(line => line.trim() && !line.startsWith('!['));
  
  for (const line of lines) {
    const trimmed = line.trim();
    if (trimmed && !trimmed.startsWith('#')) {
      // Limit description length
      return trimmed.length > 200 ? trimmed.substring(0, 200) + '...' : trimmed;
    }
  }
  
  return 'No description available';
}

/**
 * Removes frontmatter from markdown content
 */
function removeFrontmatter(content: string): string {
  // Remove frontmatter (YAML between --- markers)
  return content.replace(/^---\s*\n[\s\S]*?\n---\s*\n/, '');
}
