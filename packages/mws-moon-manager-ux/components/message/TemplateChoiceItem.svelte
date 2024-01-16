<svelte:options customElement="mws-msg-template-choice-item" />

<script>
  // https://svelte.dev/docs/custom-elements-api
	export let name = 'world';
	export let question;
	export let item;
  console.log('Custom view for : ', question, item);
</script>

<h1>Hello {name}!</h1>
<slot />