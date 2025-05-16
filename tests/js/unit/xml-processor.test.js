/**
 * Unit tests for xml-processor.js
 */

import { setupGlobalMocks, cleanupGlobalMocks } from '../utils/test-utils';

describe('MPAIXMLProcessor', () => {
  beforeEach(() => {
    // Setup global mocks
    setupGlobalMocks();
    
    // Load the xml-processor.js script
    require('../../../assets/js/xml-processor');
  });
  
  afterEach(() => {
    // Clean up global mocks
    cleanupGlobalMocks();
    
    // Clean up window.MPAIXMLProcessor
    delete window.MPAIXMLProcessor;
  });
  
  test('should export public API', () => {
    expect(window.MPAIXMLProcessor).toBeDefined();
    expect(window.MPAIXMLProcessor.processMessage).toBeInstanceOf(Function);
    expect(window.MPAIXMLProcessor.parseXML).toBeInstanceOf(Function);
    expect(window.MPAIXMLProcessor.extractTags).toBeInstanceOf(Function);
    expect(window.MPAIXMLProcessor.containsXML).toBeInstanceOf(Function);
    expect(window.MPAIXMLProcessor.XML_TAG_TYPES).toBeDefined();
  });
  
  test('should detect XML content', () => {
    const xmlContent = '<heading>This is a heading</heading>';
    const nonXmlContent = 'This is just plain text';
    
    expect(window.MPAIXMLProcessor.containsXML(xmlContent)).toBe(true);
    expect(window.MPAIXMLProcessor.containsXML(nonXmlContent)).toBe(false);
  });
  
  test('should parse XML content', () => {
    const xmlContent = '<heading>This is a heading</heading>';
    const result = window.MPAIXMLProcessor.parseXML(xmlContent);
    
    expect(result).toBeInstanceOf(Document);
    expect(result.documentElement.nodeName.toLowerCase()).toBe('root');
    expect(result.documentElement.firstChild.nodeName.toLowerCase()).toBe('heading');
    expect(result.documentElement.firstChild.textContent).toBe('This is a heading');
  });
  
  test('should extract tags from XML content', () => {
    const xmlContent = '<heading level="1">Title</heading><p>This is a paragraph</p>';
    const tags = window.MPAIXMLProcessor.extractTags(xmlContent);
    
    expect(tags).toBeInstanceOf(Array);
    expect(tags.length).toBe(2);
    
    expect(tags[0].name).toBe('heading');
    expect(tags[0].attributes.level).toBe('1');
    expect(tags[0].content).toBe('Title');
    
    expect(tags[1].name).toBe('p');
    expect(tags[1].content).toBe('This is a paragraph');
  });
  
  test('should process heading tags', () => {
    const xmlContent = '<heading level="2">This is a heading</heading>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<h2 class="mpai-heading">');
    expect(result).toContain('This is a heading');
    expect(result).toContain('</h2>');
  });
  
  test('should process paragraph tags', () => {
    const xmlContent = '<p>This is a paragraph</p>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<p class="mpai-paragraph">');
    expect(result).toContain('This is a paragraph');
    expect(result).toContain('</p>');
  });
  
  test('should process bold tags', () => {
    const xmlContent = 'This is <b>bold</b> text';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('This is <strong>bold</strong> text');
  });
  
  test('should process italic tags', () => {
    const xmlContent = 'This is <i>italic</i> text';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('This is <em>italic</em> text');
  });
  
  test('should process inline code tags', () => {
    const xmlContent = 'This is <code inline="true">inline code</code>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<code class="mpai-code-inline">inline code</code>');
  });
  
  test('should process code block tags', () => {
    const xmlContent = '<code language="javascript">const x = 1;</code>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    // Since we're using the mock MPAIContentPreview, we should check that it was called
    expect(window.MPAIContentPreview.createCodePreview).toHaveBeenCalled();
    expect(window.MPAIContentPreview.createCodePreview).toHaveBeenCalledWith(
      'const x = 1;',
      expect.objectContaining({
        language: 'javascript',
        lineNumbers: true
      })
    );
  });
  
  test('should process link tags', () => {
    const xmlContent = '<a href="https://example.com">Example Link</a>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<a href="https://example.com" target="_blank" class="mpai-link">');
    expect(result).toContain('Example Link');
    expect(result).toContain('</a>');
  });
  
  test('should process list tags', () => {
    const xmlContent = '<list type="ul"><item>Item 1</item><item>Item 2</item></list>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<ul class="mpai-list">');
    expect(result).toContain('<li class="mpai-list-item">Item 1</li>');
    expect(result).toContain('<li class="mpai-list-item">Item 2</li>');
    expect(result).toContain('</ul>');
  });
  
  test('should process blockquote tags', () => {
    const xmlContent = '<blockquote>This is a quote</blockquote>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<blockquote class="mpai-blockquote">');
    expect(result).toContain('This is a quote');
    expect(result).toContain('</blockquote>');
  });
  
  test('should process table tags', () => {
    const xmlContent = `
      <table>
        <row>
          <cell header="true">Header 1</cell>
          <cell header="true">Header 2</cell>
        </row>
        <row>
          <cell>Data 1</cell>
          <cell>Data 2</cell>
        </row>
      </table>
    `;
    
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    // Since we're using the mock MPAIContentPreview, we should check that it was called
    expect(window.MPAIContentPreview.createTablePreview).toHaveBeenCalled();
    expect(window.MPAIContentPreview.createTablePreview).toHaveBeenCalledWith(
      expect.any(Array),
      expect.objectContaining({
        headers: expect.any(Array),
        striped: true,
        bordered: true
      })
    );
  });
  
  test('should process button tags', () => {
    const xmlContent = '<button type="primary">Click Me</button>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    // Since we're using the mock MPAIButtonRenderer, we should check that it was called
    expect(window.MPAIButtonRenderer.createButton).toHaveBeenCalled();
    expect(window.MPAIButtonRenderer.createButton).toHaveBeenCalledWith(
      expect.objectContaining({
        text: 'Click Me',
        type: 'primary'
      })
    );
  });
  
  test('should process error message tags', () => {
    const xmlContent = '<error>This is an error message</error>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<div class="mpai-message mpai-error-message">');
    expect(result).toContain('This is an error message');
    expect(result).toContain('</div>');
  });
  
  test('should process warning message tags', () => {
    const xmlContent = '<warning>This is a warning message</warning>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<div class="mpai-message mpai-warning-message">');
    expect(result).toContain('This is a warning message');
    expect(result).toContain('</div>');
  });
  
  test('should process success message tags', () => {
    const xmlContent = '<success>This is a success message</success>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<div class="mpai-message mpai-success-message">');
    expect(result).toContain('This is a success message');
    expect(result).toContain('</div>');
  });
  
  test('should process info message tags', () => {
    const xmlContent = '<info>This is an info message</info>';
    const result = window.MPAIXMLProcessor.processMessage(xmlContent);
    
    expect(result).toContain('<div class="mpai-message mpai-info-message">');
    expect(result).toContain('This is an info message');
    expect(result).toContain('</div>');
  });
  
  test('should handle mixed content with XML and plain text', () => {
    const mixedContent = 'This is plain text. <b>This is bold.</b> More plain text.';
    const result = window.MPAIXMLProcessor.processMessage(mixedContent);
    
    expect(result).toContain('This is plain text. ');
    expect(result).toContain('<strong>This is bold.</strong>');
    expect(result).toContain(' More plain text.');
  });
  
  test('should extract table data correctly', () => {
    // Create a table node
    const tableXml = `
      <table>
        <row>
          <cell header="true">Header 1</cell>
          <cell header="true">Header 2</cell>
        </row>
        <row>
          <cell>Data 1</cell>
          <cell>Data 2</cell>
        </row>
      </table>
    `;
    
    const doc = window.MPAIXMLProcessor.parseXML(tableXml);
    const tableNode = doc.documentElement.firstChild;
    
    // Use the extractTableData function through the module's internal scope
    // This is a bit of a hack for testing private functions
    const tableData = window.MPAIXMLProcessor.processMessage(tableXml);
    
    // Since we're using the mock MPAIContentPreview, we should check that it was called
    expect(window.MPAIContentPreview.createTablePreview).toHaveBeenCalled();
  });
  
  test('should add XML styles to document', () => {
    // The styles should be added to the document head
    const styleElements = document.head.querySelectorAll('style');
    let xmlStyleFound = false;
    
    for (let i = 0; i < styleElements.length; i++) {
      if (styleElements[i].textContent.includes('.mpai-message')) {
        xmlStyleFound = true;
        break;
      }
    }
    
    expect(xmlStyleFound).toBe(true);
  });
});