// ----------------------------------------------------------------------------
// markItUp!
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// BBCode tags example
// http://en.wikipedia.org/wiki/Bbcode
// ----------------------------------------------------------------------------
// Feel free to add more tags
// ----------------------------------------------------------------------------
myBbcodeSettings = {
  root: sdurl + 'includes/javascript/markitup/',
  nameSpace: 'bbcode', // Useful to prevent multi-instances CSS conflict
  onTab: {keepDefault:false, replaceWith:'  '},
  previewParserPath:  '',
  previewTemplatePath: '',
  resizeHandle: true,
  markupSet: [
      {name:'Bold', key:'B', openWith:'[b]', closeWith:'[/b]'},
      {name:'Italic', key:'I', openWith:'[i]', closeWith:'[/i]'},
      {name:'Underline', key:'U', openWith:'[u]', closeWith:'[/u]'},
      {name:'Picture', key:'P', replaceWith:'[img][![Url]!][/img]'},
      {name:'Link', key:'L', openWith:'[url=[![Url]!]]', closeWith:'[/url]', placeHolder:'Your text to link here...'},
      {name:'Colors', openWith:'[color=[![Color]!]]', closeWith:'[/color]', className:"colors", dropMenu: [
          {name:'Yellow', openWith:'[color=yellow]', closeWith:'[/color]', className:"col1-1" },
          {name:'Orange', openWith:'[color=orange]', closeWith:'[/color]', className:"col1-2" },
          {name:'Red', openWith:'[color=red]', closeWith:'[/color]', className:"col1-3" },
          {name:'Blue', openWith:'[color=blue]', closeWith:'[/color]', className:"col2-1" },
          {name:'Purple', openWith:'[color=purple]', closeWith:'[/color]', className:"col2-2" },
          {name:'Green', openWith:'[color=green]', closeWith:'[/color]', className:"col2-3" },
          {name:'White', openWith:'[color=white]', closeWith:'[/color]', className:"col3-1" },
          {name:'Gray', openWith:'[color=gray]', closeWith:'[/color]', className:"col3-2" },
          {name:'Black', openWith:'[color=black]', closeWith:'[/color]', className:"col3-3" }
      ]},
      {name:'Size', key:'S', openWith:'[size=[![Text size]!]]', closeWith:'[/size]', dropMenu :[
          {name:'Big', openWith:'[size=5]', closeWith:'[/size]' },
          {name:'Normal', openWith:'[size=3]', closeWith:'[/size]' },
          {name:'Small', openWith:'[size=1]', closeWith:'[/size]' }
      ]},
      {name:'Smileys', className:"smileys", dropMenu: [
          {name:'Smile', openWith:':) ', className:"smil-smile col1-1" },
          {name:'Big Smile', openWith:':D ', className:"smil-bigsmile col1-2" },
          {name:'Laugh', openWith:'XD ', className:"smil-laugh col1-3" },
          {name:'Angry', openWith:'D: ', className:"smil-angry col1-4" },
          {name:'Neutral', openWith:'=| ', className:"smil-neutral col2-1" },
          {name:'Confused', openWith:':? ', className:"smil-confuse col2-2" },
          {name:'Surprised', openWith:':O ', className:"smil-surprise col2-3" },
          {name:'Cool', openWith:'B-) ', className:"smil-cool col2-4" },
          {name:'Tongue', openWith:':P ', className:"smil-tongue col3-1" },
          {name:'Worry', openWith:'=s ', className:"smil-worry col3-2" },
          {name:'Wink', openWith:';) ', className:"smil-wink col3-3" },
          {name:'Sleepy', openWith:':zzz: ', className:"smil-sleepy col3-4" },
          {name:'Blush', openWith:':P ', className:"smil-blush col4-1" },
          {name:'Saint', openWith:'O:) ', className:"smil-saint col4-2" },
          {name:'Blue', openWith:':blue: ', className:"smil-blue col4-3" },
          {name:'Frown', openWith:':( ', className:"smil-frown col4-4" },
          {name:'Sweat', openWith:'^^; ', className:"smil-sweat col5-1" },
          {name:'Cake', openWith:':cake: ', className:"smil-cake col5-2" },
          {name:'Star', openWith:':star: ', className:"smil-star col5-3" },
          {name:'Heart', openWith:':heart: ', className:"smil-heart col5-4" }
      ]},
      {name:'Bulleted list', openWith:'[list]\n', closeWith:'\n[/list]'},
      {name:'Numeric list', openWith:'[list=[![Starting number]!]]\n', closeWith:'\n[/list]'},
      {name:'List item', openWith:'[*] '},
      {name:'Quotes', openWith:'[quote]', closeWith:'[/quote]'},
      {name:'Code', openWith:'[code]', closeWith:'[/code]'}
   ]
}
